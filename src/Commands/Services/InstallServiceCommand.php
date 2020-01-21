<?php declare(strict_types=1);

namespace Somnambulist\ProjectManager\Commands\Services;

use Somnambulist\ProjectManager\Commands\AbstractCommand;
use Somnambulist\ProjectManager\Commands\Behaviours\DockerAwareCommand;
use Somnambulist\ProjectManager\Commands\Behaviours\GetCurrentActiveProject;
use Somnambulist\ProjectManager\Commands\Behaviours\GetServicesFromInput;
use Somnambulist\ProjectManager\Commands\Behaviours\InstallableResourceSetupHelpers;
use Somnambulist\ProjectManager\Commands\Behaviours\ProjectConfigAwareCommand;
use Somnambulist\ProjectManager\Contracts\DockerAwareInterface;
use Somnambulist\ProjectManager\Contracts\ProjectConfigAwareInterface;
use Somnambulist\ProjectManager\Exceptions\ResourceAlreadyInstalled;
use Somnambulist\ProjectManager\Exceptions\ResourceIsNotConfigured;
use Somnambulist\ProjectManager\Models\Service;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class InstallServiceCommand
 *
 * @package    Somnambulist\ProjectManager\Commands\Services
 * @subpackage Somnambulist\ProjectManager\Commands\Services\InstallServiceCommand
 */
class InstallServiceCommand extends AbstractCommand implements DockerAwareInterface, ProjectConfigAwareInterface
{

    use GetServicesFromInput;
    use GetCurrentActiveProject;
    use DockerAwareCommand;
    use InstallableResourceSetupHelpers;
    use ProjectConfigAwareCommand;

    protected function configure()
    {
        $default = $_SERVER['PROJECT_SERVICES_DIR'];

        $this
            ->setName('services:install')
            ->setDescription('Installs the specified services into the project folder from the repository')
            ->addArgument('service', InputArgument::REQUIRED|InputArgument::IS_ARRAY, 'The service to install, or "all"; see <info>services:list</info> for available services')
            ->setHelp(<<<HLP

A service is a project that runs in Docker and provides assorted runtime services.

This command will checkout the specified service configured in the services
definitions to the configured project directory. If the service is set as
"all", then all services will be checked out into the specified project folder.

The default project folder will be: <info>$default</info>

The folder structure is controlled by the settings in the <comment>project.yaml</comment>
file in the project configuration folder. By default, libraries and services are
located in the root project folder. An alternative folder name can be set by
specifying the folder name for <comment>services_dirname</comment>. 

See <info>services:list</info> for a list of available services for install.

HLP
)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->setupConsoleHelper($input, $output);

        $project  = $this->getActiveProject();

        $this->tools()->info('installing service(s) in <info>%s</info>', $project->name());

        $services = $this->getServicesFrom($input, 'installing all services, this might take a while...');

        foreach ($services as $name) {
            try {
                /** @var Service $resource */
                $resource = $this->assertResourceIsConfigured($project->services(), $name);

                $this->assertNotInstalled($resource);
                $this->createProjectDirIfNotExists($resource);
                $this->createCloneOfRepository($resource);

                $this->tools()->info('pre-building docker containers ... ');

                if (!$this->docker->build($resource)) {
                    $this->tools()->error('failed to build containers, switch to the project and run: <info>docker-compose build/up</info>');

                    continue;
                }

                $this->tools()->success('containers built <info>successfully</info>');

            } catch (ResourceIsNotConfigured | ResourceAlreadyInstalled $e) {
                $this->tools()->error($e->getMessage());
                continue;
            }

            $this->tools()->success('service setup completed <info>successfully</info>');
        }

        return 0;
    }
}
