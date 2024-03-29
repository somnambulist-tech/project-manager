<?php declare(strict_types=1);

namespace Somnambulist\ProjectManager\Services;

use Phar;
use RuntimeException;
use SplFileInfo;
use Symfony\Component\Finder\Finder;
use function file_exists;
use function shell_exec;
use function str_replace;
use function strcmp;
use function strtr;

/**
 * Compiler
 *
 * Shamelessly ripped from Composer.
 *
 * @link https://github.com/composer/composer/blob/master/src/Composer/Compiler.php
 */
class Compiler
{
    private function basePath(): string
    {
        return __DIR__ . '/../..';
    }

    /**
     * Compiles PPM into a single phar file
     *
     * @param string $pharFile The full path to the file to create
     *
     * @throws RuntimeException
     */
    public function compile(string $pharFile = 'somnambulist-project-manager.phar'): void
    {
        if (file_exists($pharFile)) {
            unlink($pharFile);
        }

        $phar = new Phar($pharFile, 0, 'somnambulist-project-manager.phar');
        $phar->startBuffering();

        $basePath = $this->basePath();

        $this->addApplicationFiles($phar, $basePath);
        $this->addDefinitionFiles($phar, $basePath);

        $testFor = [
            'include_paths.php', 'platform_check.php', 'installed.php', 'InstalledVersions.php', 'installed.json',
        ];

        $this->addFile($phar, new SplFileInfo($basePath . '/vendor/autoload.php'));
        $this->addFile($phar, new SplFileInfo($basePath . '/vendor/composer/autoload_classmap.php'));
        $this->addFile($phar, new SplFileInfo($basePath . '/vendor/composer/autoload_files.php'));
        $this->addFile($phar, new SplFileInfo($basePath . '/vendor/composer/autoload_namespaces.php'));
        $this->addFile($phar, new SplFileInfo($basePath . '/vendor/composer/autoload_psr4.php'));
        $this->addFile($phar, new SplFileInfo($basePath . '/vendor/composer/autoload_real.php'));
        $this->addFile($phar, new SplFileInfo($basePath . '/vendor/composer/autoload_static.php'));
        $this->addFile($phar, new SplFileInfo($basePath . '/vendor/composer/ClassLoader.php'));

        foreach ($testFor as $test) {
            if (file_exists($basePath . '/vendor/composer/' . $test)) {
                $this->addFile($phar, new SplFileInfo($basePath . '/vendor/composer/' . $test));
            }
        }

        $this->addBin($phar);

        // Stubs
        $phar->setStub($this->getStub());
        $phar->compressFiles(Phar::GZ);

        $phar->stopBuffering();

        unset($phar);

        chmod($pharFile, 0755);
    }

    private function addApplicationFiles(Phar $phar, string $basePath): void
    {
        $finder = new Finder();
        $finder
            ->files()
            ->ignoreVCS(true)
            ->name(['*.php', '*.yaml', '*.yml', '*.md', '*.xml'])
            ->name('LICENSE')
            ->name('dockerignore')
            ->name('gitignore')
            ->exclude('Tests')
            ->exclude('tests')
            ->exclude('docs')
            ->exclude('config/definitions')
            ->notName('create-phar.php')
            ->in($basePath . '/config/')
            ->in($basePath . '/src/')
            ->in($basePath . '/var/cache/prod/')
            ->in($basePath . '/vendor/pragmarx/')
            ->in($basePath . '/vendor/psr/')
            ->in($basePath . '/vendor/somnambulist/')
            ->in($basePath . '/vendor/symfony/')
            ->in($basePath . '/vendor/voku/')
            ->sort(fn (SplFileInfo $a, SplFileInfo $b) => strcmp(strtr($a->getRealPath(), '\\', '/'), strtr($b->getRealPath(), '\\', '/')))
        ;

        foreach ($finder as $file) {
            $this->addFile($phar, $file);
        }
    }

    private function addDefinitionFiles(Phar $phar, string $basePath): void
    {
        $finder = new Finder();
        $finder
            ->files()
            ->in($basePath . '/config/definitions/')
            ->name('*')
            ->ignoreVCS(true)
            ->sort(fn (SplFileInfo $a, SplFileInfo $b) => strcmp(strtr($a->getRealPath(), '\\', '/'), strtr($b->getRealPath(), '\\', '/')))
        ;

        foreach ($finder as $file) {
            $this->addFile($phar, $file);
        }
    }

    private function addFile(Phar $phar, SplFileInfo $file): void
    {
        $path    = strtr(str_replace(realpath($this->basePath()), '', $file->getRealPath()), '\\', '/');
        $content = file_get_contents($file->getRealPath());

        if ('LICENSE' === $file->getBasename()) {
            $content = "\n" . $content . "\n";
        } else {
            $content = $this->stripWhitespace($content);
        }

        $phar->addFromString($path, $content);
    }

    private function addBin(Phar $phar): void
    {
        $content = file_get_contents($this->basePath() . '/bin/console');
        $content = preg_replace('{^#!/usr/bin/env php\s*}', '', $content);
        $content = strtr($content, [
            'new Kernel($_SERVER[\'APP_ENV\'], (bool)$_SERVER[\'APP_DEBUG\']);' => 'new Kernel(\'prod\', false);',
            'new Application($kernel, \'1.0.0\')' => sprintf('new Application($kernel, \'%s\')', $this->getMostRecentTagFromRepository()),
        ]);

        $phar->addFromString('bin/console', $content);
    }

    private function getMostRecentTagFromRepository(): string
    {
        return trim(shell_exec('git describe --abbrev=0 --tags') ?? 'latest');
    }

    /**
     * Removes whitespace from a PHP source string while preserving line numbers.
     *
     * @param string $source A PHP string
     *
     * @return string The PHP string with the whitespace removed
     */
    private function stripWhitespace(string $source): string
    {
        if (!function_exists('token_get_all')) {
            return $source;
        }

        $output = '';
        foreach (token_get_all($source) as $token) {
            if (is_string($token)) {
                $output .= $token;
            } elseif (in_array($token[0], [T_COMMENT, T_DOC_COMMENT])) {
                $output .= str_repeat("\n", substr_count($token[1], "\n"));
            } elseif (T_WHITESPACE === $token[0]) {
                // reduce wide spaces
                $whitespace = preg_replace('{[ \t]+}', ' ', $token[1]);
                // normalize newlines to \n
                $whitespace = preg_replace('{(?:\r\n|\r|\n)}', "\n", $whitespace);
                // trim leading spaces
                $whitespace = preg_replace('{\n +}', "\n", $whitespace);
                $output     .= $whitespace;
            } else {
                $output .= $token[1];
            }
        }

        return $output;
    }

    private function getStub(): string
    {
        return <<<'EOF'
#!/usr/bin/env php
<?php

// Copied from Composer stub
// Avoid APC causing random fatal errors per https://github.com/composer/composer/issues/264
if (extension_loaded('apc') && ini_get('apc.enable_cli') && ini_get('apc.cache_by_default')) {
    if (version_compare(phpversion('apc'), '3.0.12', '>=')) {
        ini_set('apc.cache_by_default', 0);
    } else {
        fwrite(STDERR, 'Warning: APC <= 3.0.12 may cause fatal errors when running commands.'.PHP_EOL);
        fwrite(STDERR, 'Update APC, or set apc.enable_cli or apc.cache_by_default to 0 in your php.ini.'.PHP_EOL);
    }
}

Phar::mapPhar('somnambulist-project-manager.phar');
require 'phar://somnambulist-project-manager.phar/bin/console';

__HALT_COMPILER();
EOF;
    }
}
