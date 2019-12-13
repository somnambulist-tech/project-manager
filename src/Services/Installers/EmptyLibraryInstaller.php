<?php declare(strict_types=1);

namespace Somnambulist\ProjectManager\Services\Installers;

use IlluminateAgnostic\Str\Support\Str;
use Somnambulist\ProjectManager\Models\Project;
use function file_put_contents;
use const DIRECTORY_SEPARATOR;

/**
 * Class EmptyLibraryInstaller
 *
 * @package    Somnambulist\ProjectManager\Services\Installers
 * @subpackage Somnambulist\ProjectManager\Services\Installers\EmptyLibraryInstaller
 */
class EmptyLibraryInstaller extends AbstractInstaller
{

    public function installInto(Project $project, string $name, string $cwd): int
    {
        $step = 0;

        $this->tools()->step(++$step, 'creating library folder');

        if (0 !== $this->createLibraryFolder($cwd)) {
            return 1;
        }

        $this->tools()->step(++$step, 'creating basic files and folder structure');

        file_put_contents($cwd . DIRECTORY_SEPARATOR . 'composer.json', $this->createComposerJsonContents($project, $name));

        $this->copyFromConfigTemplates('gitignore', $cwd . DIRECTORY_SEPARATOR . '.gitignore');
        $this->copyFromConfigTemplates('phpunit.xml', $cwd . DIRECTORY_SEPARATOR . 'phpunit.xml.dist');
        $this->copyFromConfigTemplates('library-readme.md', $cwd . DIRECTORY_SEPARATOR . 'readme.md');

        foreach (['src', 'tests'] as $dir) {
            $this->createGitKeepAt($cwd . DIRECTORY_SEPARATOR . $dir);
        }

        $this->tools()->step(++$step, 'creating git repository');

        if (0 !== $this->initialiseGitRepositoryAt($cwd)) {
            return 1;
        }

        $this->updateProjectConfig($project, ++$step);

        return $this->success();
    }

    private function createComposerJsonContents(Project $project, string $name): string
    {
        $ns    = Str::studly($project->name());
        $class = Str::studly($name);
        $proj  = Str::slug($project->name());

        return <<<COMP
{
    "name": "$proj/$name",
    "type": "library",
    "description": "$name",
    "license": "proprietary",
    "autoload": {
        "psr-4": {
            "$ns\\$class\\": "src/"
        }
    },
    "autoload-dev": {
        "psr-4": {
            "$ns\\$class\\Tests\\": "tests/"
        }
    },
    "require": {
        "php": ">=7.3"
    },
    "require-dev": {
        "phpunit/phpunit": "~8.2"
    }
}

COMP;
    }
}
