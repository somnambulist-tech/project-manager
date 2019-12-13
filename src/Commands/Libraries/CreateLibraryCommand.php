<?php declare(strict_types=1);

namespace Somnambulist\ProjectManager\Commands\Libraries;

use IlluminateAgnostic\Str\Support\Str;
use Somnambulist\ProjectManager\Commands\AbstractCommand;
use Somnambulist\ProjectManager\Commands\Behaviours\CanInitialiseGitRepository;
use Somnambulist\ProjectManager\Commands\Behaviours\GetCurrentActiveProject;
use Somnambulist\ProjectManager\Commands\Behaviours\ProjectConfigAwareCommand;
use Somnambulist\ProjectManager\Contracts\ProjectConfigAwareInterface;
use Somnambulist\ProjectManager\Models\Library;
use Somnambulist\ProjectManager\Models\Project;
use Somnambulist\ProjectManager\Models\Template;
use Somnambulist\ProjectManager\Services\Config\ExportProjectToYaml;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use function file_put_contents;
use function getenv;
use function is_dir;
use function mkdir;
use function sprintf;
use function touch;
use const DIRECTORY_SEPARATOR;

/**
 * Class CreateLibraryCommand
 *
 * @package    Somnambulist\ProjectManager\Commands\Libraries
 * @subpackage Somnambulist\ProjectManager\Commands\Libraries\CreateLibraryCommand
 */
class CreateLibraryCommand extends AbstractCommand implements ProjectConfigAwareInterface
{

    use GetCurrentActiveProject;
    use ProjectConfigAwareCommand;
    use CanInitialiseGitRepository;

    protected function configure()
    {
        $default = getenv('PROJECT_LIBRARIES_DIR');

        $this
            ->setName('libraries:create')
            ->setDescription('Creates a new library within the currently active project')
            ->addArgument('name', InputArgument::OPTIONAL, 'The name of the library to create or blank to use the wizard')
            ->addArgument('template', InputArgument::OPTIONAL, 'The name of the template to use for scaffolding the library')
            ->setHelp(<<<HLP

The library will be created in: <info>$default</info>

The folder structure is controlled by the settings in the <comment>project.yaml</comment>
file in the project configuration folder. By default, libraries and services are
located in the root project folder. An alternative folder name can be set by
specifying the folder name for <comment>libraries_dirname</comment>.

Note: the library name must be unique as it is the name of the folder.

HLP
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->setupConsoleHelper($input, $output);

        $project  = $this->getActiveProject();
        $name     = $input->getArgument('name');
        $template = $input->getArgument('template');

        $this->tools()->warning('Creating new library for <comment>%s</comment>', $project->name());

        if (!$name) {
            $name = $this->tools()->ask('What will your library be called? This is the local folder name: ');
        }
        if (!$template) {
            $template = $this->tools()->choose('Which template would you like to use?', $this->config->availableTemplates('library'));
        }
        if (null === $temp = $project->templates()->get($template)) {
            if (null === $temp = $this->config->templates()->get($template)) {
                $this->tools()->error('there is no template <comment>%s</comment> in the project or the core', $template);
                $this->tools()->newline();

                return 1;
            }

            $template = $temp;
        }

        $cwd = $_SERVER['PROJECT_LIBRARIES_DIR'] . DIRECTORY_SEPARATOR . $name;

        $project->libraries()->add(new Library($name, $name));

        switch (true):
            case $template->isGitResource():
                return $this->setupGitResource($project, $template, $name, $cwd);
                break;

            case $template->isComposerResource():
                return $this->setupComposerResource($project, $template, $name, $cwd);
                break;

            case $template->hasResource():
                return $this->setupFromConfiguration($project, $template, $name, $cwd);
                break;

            default:
                return $this->setupBlankFolder($project, $name, $cwd);
        endswitch;
    }

    private function setupGitResource(Project $project, Template $template, string $name, string $cwd)
    {
        $step = 0;

        $this->tools()->step(++$step, 'creating library from git repository');

        $res = $this->tools()->execute(
            sprintf(
                'git clone --depth=1 %s %s',
                Str::replaceFirst('git:', '', $template->source()),
                $cwd
            ),
            $cwd
        );

        if (!$res) {
            $this->tools()->error('failed to clone git repository, re-run with <info>-vvv</info> to get debugging');
            $this->tools()->info('do you have permission to access the repository?');
            $this->tools()->newline();

            return 1;
        }

        if (!$this->tools()->execute(sprintf('rm -rf %s/.git', $cwd))) {
            $this->tools()->error('failed to remove .git from <comment>%s</comment>', $cwd);
            $this->tools()->newline();

            return 1;
        }

        $this->tools()->step(++$step, 'creating git repository');

        if (0 !== $this->initialiseGitRepositoryAt($cwd)) {
            return 1;
        }

        $this->updateProjectConfig($project, ++$step);

        return $this->success();
    }

    private function setupComposerResource(Project $project, Template $template, string $name, string $cwd)
    {
        $step = 0;

        $this->tools()->step(++$step, 'creating library from composer project');
        $this->tools()->warning('installer scripts will not be run!');

        $res = $this->tools()->execute(
            sprintf(
                'composer create-project --no-scripts --remove-vcs %s %s',
                Str::replaceFirst('composer:', '', $template->source()),
                $cwd
            ),
            $cwd
        );

        if (!$res) {
            $this->tools()->error('failed to create project via composer, re-run with <info>-vvv</info> to get debugging');
            $this->tools()->newline();

            return 1;
        }

        $this->tools()->step(++$step, 'creating git repository');

        if (0 !== $this->initialiseGitRepositoryAt($cwd)) {
            return 1;
        }

        $this->updateProjectConfig($project, ++$step);

        return $this->success();
    }

    private function setupFromConfiguration(Project $project, Template $template, string $name, string $cwd)
    {
        $step = 0;

        $this->tools()->step(++$step, 'creating library folder');

        if (0 !== $this->createLibraryFolder($cwd)) {
            return 1;
        }

        $this->tools()->step(++$step, 'copying template files to library');

        $source = $project->configPath() . DIRECTORY_SEPARATOR . $template->source();

        if (!is_dir($source)) {
            $this->tools()->error('template folder not found at <comment>%s</comment>', $source);
            $this->tools()->newline();

            return 1;
        }

        $this->tools()->execute(sprintf('cp -R %s %s', $source, $cwd));

        $this->tools()->step(++$step, 'creating git repository');

        if (0 !== $this->initialiseGitRepositoryAt($cwd)) {
            return 1;
        }

        $this->updateProjectConfig($project, ++$step);

        return $this->success();
    }

    private function setupBlankFolder(Project $project, string $name, string $cwd)
    {
        $step = 0;

        $this->tools()->step(++$step, 'creating library folder');

        if (0 !== $this->createLibraryFolder($cwd)) {
            return 1;
        }

        $this->tools()->step(++$step, 'creating basic files and folder structure');

        file_put_contents($cwd . DIRECTORY_SEPARATOR . 'composer.json', $this->composer($project, $name));
        file_put_contents($cwd . DIRECTORY_SEPARATOR . '.gitignore', $this->gitignore());
        file_put_contents($cwd . DIRECTORY_SEPARATOR . 'phpunit.xml.dist', $this->phpunit());
        touch($cwd . DIRECTORY_SEPARATOR . 'readme.md');
        mkdir($cwd . DIRECTORY_SEPARATOR . 'src');
        touch($cwd . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . '.gitkeep');
        mkdir($cwd . DIRECTORY_SEPARATOR . 'tests');
        touch($cwd . DIRECTORY_SEPARATOR . 'tests' . DIRECTORY_SEPARATOR . '.gitkeep');

        $this->tools()->step(++$step, 'creating git repository');

        if (0 !== $this->initialiseGitRepositoryAt($cwd)) {
            return 1;
        }

        $this->updateProjectConfig($project, ++$step);

        return $this->success();
    }

    private function updateProjectConfig(Project $project, int $step): int
    {
        $this->tools()->step($step, 'updating project configuration');

        (new ExportProjectToYaml())->export($project, $project->configFile());

        $this->tools()->success('project config updated successfully');

        return 0;
    }

    private function success(): int
    {
        $this->tools()->success('library scaffold created <info>successfully</info>');
        $this->tools()->info('be sure to add the remote git repository once it is configured to the config file');
        $this->tools()->newline();

        return 0;
    }

    private function createLibraryFolder(string $cwd): int
    {
        if (!mkdir($cwd, 0775, true)) {
            $this->tools()->error('unable to create folder at <comment>%s</comment>', $cwd);
            $this->tools()->newline();

            return 1;
        }

        return 0;
    }

    private function composer(Project $project, string $name): string
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

    private function gitignore(): string
    {
        return <<<GIT
# Created by .ignore support plugin (hsz.mobi)
### JetBrains template
# Covers JetBrains IDEs: IntelliJ, RubyMine, PhpStorm, AppCode, PyCharm, CLion, Android Studio and Webstorm
# Reference: https://intellij-support.jetbrains.com/hc/en-us/articles/206544839

# Ignore all of .idea
#.idea

# User-specific stuff:
.idea/**/workspace.xml
.idea/**/tasks.xml
.idea/dictionaries

# Sensitive or high-churn files:
.idea/**/dataSources/
.idea/**/dataSources.ids
.idea/**/dataSources.xml
.idea/**/dataSources.local.xml
.idea/**/sqlDataSources.xml
.idea/**/dynamic.xml
.idea/**/uiDesigner.xml

# Gradle:
.idea/**/gradle.xml
.idea/**/libraries

# Mongo Explorer plugin:
.idea/**/mongoSettings.xml

## File-based project format:
*.iws

## Plugin-specific files:

# IntelliJ
/out/

# mpeltonen/sbt-idea plugin
.idea_modules/

# JIRA plugin
atlassian-ide-plugin.xml

# Crashlytics plugin (for Android Studio and IntelliJ)
com_crashlytics_export_strings.xml
crashlytics.properties
crashlytics-build.properties
fabric.properties

### Composer template
composer.phar
/vendor/
/var/
.env*
.phpunit.result.cache

# Commit your application's lock file http://getcomposer.org/doc/01-basic-usage.md#composer-lock-the-lock-file
# You may choose to ignore a library lock file http://getcomposer.org/doc/02-libraries.md#lock-file
composer.lock

### macOS template
*.DS_Store
.AppleDouble
.LSOverride

# Icon must end with two \r
Icon

# Thumbnails
._*

# Files that might appear in the root of a volume
.DocumentRevisions-V100
.fseventsd
.Spotlight-V100
.TemporaryItems
.Trashes
.VolumeIcon.icns
.com.apple.timemachine.donotpresent

# Directories potentially created on remote AFP share
.AppleDB
.AppleDesktop
Network Trash Folder
Temporary Items
.apdisk

GIT;
    }

    private function phpunit(): string
    {
        return <<<XML
<?xml version="1.0" encoding="UTF-8"?>

<!-- https://phpunit.readthedocs.io/en/latest/configuration.html -->
<phpunit xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
         xsi:noNamespaceSchemaLocation="http://schema.phpunit.de/8.2/phpunit.xsd"
         colors="true"
         bootstrap="vendor/autoload.php"
>
    <php>
        <ini name="error_reporting" value="-1" />
        <ini name="memory_limit" value="256M" />
        <server name="APP_ENV" value="test" force="true" />
        <server name="SHELL_VERBOSITY" value="-1" />
        <server name="SYMFONY_PHPUNIT_REMOVE" value="" />
        <server name="SYMFONY_PHPUNIT_VERSION" value="8" />
        <server name="SYMFONY_DEPRECATIONS_HELPER" value="disabled" />
    </php>

    <testsuites>
        <testsuite name="Project Test Suite">
            <directory>tests</directory>
        </testsuite>
    </testsuites>

    <filter>
        <whitelist processUncoveredFilesFromWhitelist="true">
            <directory suffix=".php">src/</directory>
            <exclude>
                <directory>tests</directory>
            </exclude>
        </whitelist>
    </filter>
</phpunit>
XML;

    }
}
