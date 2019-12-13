<?php declare(strict_types=1);

namespace Somnambulist\ProjectManager\Services\Installers;

use IlluminateAgnostic\Str\Support\Str;
use Somnambulist\ProjectManager\Models\Project;
use function file_put_contents;
use const DIRECTORY_SEPARATOR;

/**
 * Class EmptyServiceInstaller
 *
 * @package    Somnambulist\ProjectManager\Services\Installers
 * @subpackage Somnambulist\ProjectManager\Services\Installers\EmptyServiceInstaller
 */
class EmptyServiceInstaller extends AbstractInstaller
{

    public function installInto(Project $project, string $name, string $cwd): int
    {
        $step = 0;

        $this->tools()->step(++$step, 'creating <info>%s</info> folder', $this->type);

        if (0 !== $this->createLibraryFolder($cwd)) {
            return 1;
        }

        $this->tools()->step(++$step, 'creating basic files and folder structure');

        file_put_contents($cwd . DIRECTORY_SEPARATOR . 'composer.json', $this->createComposerJsonContents($project, $name));

        $this->copyFromConfigTemplates('docker-compose.yml', $cwd . DIRECTORY_SEPARATOR . 'docker-compose.yml');
        $this->copyFromConfigTemplates('dockerignore', $cwd . DIRECTORY_SEPARATOR . '.dockerignore');
        $this->copyFromConfigTemplates('gitignore', $cwd . DIRECTORY_SEPARATOR . '.gitignore');
        $this->copyFromConfigTemplates('mutagen_sync_it.yaml', $cwd . DIRECTORY_SEPARATOR . '.mutagen_sync_it.yaml');
        $this->copyFromConfigTemplates('phpunit.xml', $cwd . DIRECTORY_SEPARATOR . 'phpunit.xml.dist');
        $this->copyFromConfigTemplates('ppm.json', $cwd . DIRECTORY_SEPARATOR . 'ppm.dist.json');
        $this->copyFromConfigTemplates('services-readme.md', $cwd . DIRECTORY_SEPARATOR . 'readme.md');

        foreach (['bin', 'public', 'src', 'tests', 'var'] as $dir) {
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
        $class = Str::studly($name);
        $proj  = Str::slug($project->name());

        return <<<COMP
{
    "name": "$proj/$name",
    "type": "project",
    "description": "Docker service for $name",
    "license": "proprietary",
    "autoload": {
        "psr-4": {
            "App\\$class\\": "src/"
        }
    },
    "autoload-dev": {
        "psr-4": {
            "App\\$class\\Tests\\": "tests/"
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
