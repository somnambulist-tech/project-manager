<?php declare(strict_types=1);

namespace Somnambulist\ProjectManager\Commands\Services;

use Somnambulist\ProjectManager\Commands\AbstractCommand;
use Somnambulist\ProjectManager\Commands\Behaviours\DockerAwareCommand;
use Somnambulist\ProjectManager\Commands\Behaviours\GetCurrentActiveProject;
use Somnambulist\ProjectManager\Commands\Behaviours\GetServicesFromInput;
use Somnambulist\ProjectManager\Commands\Behaviours\ProjectConfigAwareCommand;
use Somnambulist\ProjectManager\Contracts\DockerAwareInterface;
use Somnambulist\ProjectManager\Contracts\ProjectConfigAwareInterface;
use Somnambulist\ProjectManager\Models\Service;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use function getcwd;

/**
 * Class RestartAppCommand
 *
 * @package    Somnambulist\ProjectManager\Commands\Services
 * @subpackage Somnambulist\ProjectManager\Commands\Services\RestartAppCommand
 */
class RestartAppCommand extends AbstractCommand implements DockerAwareInterface, ProjectConfigAwareInterface
{

    use GetServicesFromInput;
    use GetCurrentActiveProject;
    use DockerAwareCommand;
    use ProjectConfigAwareCommand;

    protected function configure(): void
    {
        $this
            ->setName('services:restart')
            ->setAliases(['restart'])
            ->setDescription('Restarts the application services for the specified services')
            ->addArgument('service', InputArgument::IS_ARRAY, 'The services to restart, or "all"; see <info>services:list</info> for available services')
            ->setHelp(<<<HLP
Restart the application service from the currently active project by specifying
the service names to start. e.g.: <info>%command.full_name% service1 service2</info>

If the command is run without any arguments, and you are in a runnable service,
that app service will be restarted, otherwise the available services will be listed.

All app services can be started by using: <info>%command.full_name% all</info>

Under the hood, this command runs <info>docker-compose restart</info> to restart just
the application service.

Note that this will not start services, only restart running services.

HLP)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->setIsDebugging($input);
        $this->setupConsoleHelper($input, $output);

        $project = $this->getActiveProject();

        $this->tools()->info('restarting service(s) in <info>%s</info>', $project->name());

        if ((null !== $service = $project->getServiceByPath(getcwd())) && !$input->getArgument('service')) {
            $services = [$service->name()];
            $this->tools()->info('restarting <info>%s</info>', $service->name());
        } else {
            $services = $this->getServicesFrom($input, 'restarting all app services', 'Select the service to restart: ');
        }

        foreach ($services as $name) {
            $this->restartService($name);
        }

        return 0;
    }

    private function restartService(string $serviceName): void
    {
        $project = $this->getActiveProject();

        /** @var Service $service */
        if (null === $service = $project->services()->get($serviceName)) {
            $this->tools()->error('service <info>%s</info> not found!', $serviceName);
            return;
        }

        if (!$service->isInstalled()) {
            $this->tools()->error('service <info>%s</info> is not installed', $service);
            return;
        }

        if (!$service->runningContainerId()) {
            $this->docker->resolve($service);
        }

        if (!$service->isRunning()) {
            $this->tools()->error('cannot restart <info>%s</info>, it is not running', $service);
            return;
        }

        if ($this->docker->restart($service)) {
            $this->tools()->success('restarted <info>%s</info> successfully', $service);
        } else {
            $this->tools()->error('failed to restart <info>%s</info>, re-run with -vvv or restart using docker-compose', $service);
        }
    }
}
