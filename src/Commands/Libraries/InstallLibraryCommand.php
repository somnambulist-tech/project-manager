<?php declare(strict_types=1);

namespace Somnambulist\ProjectManager\Commands\Libraries;

use Somnambulist\ProjectManager\Commands\AbstractCommand;
use Somnambulist\ProjectManager\Commands\Behaviours\GetCurrentActiveProject;
use Somnambulist\ProjectManager\Commands\Behaviours\GetLibrariesFromInput;
use Somnambulist\ProjectManager\Commands\Behaviours\InstallableResourceSetupHelpers;
use Somnambulist\ProjectManager\Commands\Behaviours\ProjectConfigAwareCommand;
use Somnambulist\ProjectManager\Contracts\ProjectConfigAwareInterface;
use Somnambulist\ProjectManager\Exceptions\ResourceAlreadyInstalled;
use Somnambulist\ProjectManager\Exceptions\ResourceIsNotConfigured;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class InstallLibraryCommand
 *
 * @package    Somnambulist\ProjectManager\Commands\Libraries
 * @subpackage Somnambulist\ProjectManager\Commands\Libraries\InstallLibraryCommand
 */
class InstallLibraryCommand extends AbstractCommand implements ProjectConfigAwareInterface
{

    use GetCurrentActiveProject;
    use GetLibrariesFromInput;
    use InstallableResourceSetupHelpers;
    use ProjectConfigAwareCommand;

    protected function configure(): void
    {
        $default = $_SERVER['PROJECT_LIBRARIES_DIR'];

        $this
            ->setName('libraries:install')
            ->setDescription('Installs the specified libraries into the project folder from the repository')
            ->addArgument('library', InputArgument::IS_ARRAY, 'The library to install, or "all"; see <info>libraries:list</info> for available libraries')
            ->setHelp(<<<HLP

A library project is a set of shared code used between services, or a project
that does not run in Docker.

This command will check out the specified library configured in the libraries
definitions to the configured project directory. If the library is set as
"all", then all libraries will be checked out into the specified project folder.

The default project folder will be: <info>$default</info>

The folder structure is controlled by the settings in the <comment>project.yaml</comment>
file in the project configuration folder. By default, libraries and services are
located in the root project folder. An alternative folder name can be set by
specifying the folder name for <comment>libraries_dirname</comment>. 

See <info>libraries:list</info> for a list of available services for setup.

HLP
)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->setupConsoleHelper($input, $output);

        $project = $this->getActiveProject();

        $this->tools()->info('installing libraries in <info>%s</info>', $project->name());

        $libraries = $this->getLibrariesFrom($input, 'installing all libraries, this might take a while...', 'Select the library to install: ');

        foreach ($libraries as $name) {
            try {
                $resource = $this->assertResourceIsConfigured($project->libraries(), $name);

                $this->assertNotInstalled($resource);
                $this->createProjectDirIfNotExists($resource);
                $this->createCloneOfRepository($resource);

            } catch (ResourceIsNotConfigured | ResourceAlreadyInstalled $e) {
                $this->tools()->error($e->getMessage());
                continue;
            }

            $this->tools()->success('library installation completed <info>successfully</info>');
        }

        return 0;
    }
}
