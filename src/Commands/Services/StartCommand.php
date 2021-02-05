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
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use function getcwd;
use function strtolower;
use function trim;

/**
 * Class StartCommand
 *
 * @package Somnambulist\ProjectManager\Commands\Services
 * @subpackage Somnambulist\ProjectManager\Commands\Services\StartCommand
 */
class StartCommand extends AbstractCommand implements DockerAwareInterface, ProjectConfigAwareInterface
{

    use GetServicesFromInput;
    use GetCurrentActiveProject;
    use DockerAwareCommand;
    use ProjectConfigAwareCommand;

    protected function configure()
    {
        $this
            ->setName('services:start')
            ->setAliases(['start'])
            ->setDescription('Starts the specified service(s)')
            ->addArgument('service', InputArgument::IS_ARRAY, 'The services to start, or "all"; see <info>services:list</info> for available services')
            ->addOption('install', 'i', InputOption::VALUE_NONE, 'Install missing dependencies before starting them, if they are not installed')
            ->addOption('rebuild', 'b', InputOption::VALUE_NONE, 'Re-build the containers before starting')
            ->addOption('refresh', 'r', InputOption::VALUE_NONE, 'Refresh the containers before starting; pulls all new images')
            ->addOption('with-deps', 'd', InputOption::VALUE_NONE, 'Start all dependencies without prompting for confirmation')
            ->addOption('without-deps', 'D', InputOption::VALUE_NONE, 'Ignore all dependencies')
            ->addOption('validate', null, InputOption::VALUE_NONE, 'After starting, validate if the primary services are running')
            ->addOption('timeout', 't', InputOption::VALUE_OPTIONAL, 'The timeout value for validation in seconds, max: 180', 60)
            ->setHelp(<<<HLP
Start services from the currently active project from anywhere by specifying
the service names to start. e.g.: <info>%command.full_name% service1 service2</info>

If the command is run without any arguments and you are in a runnable service
that service will be started, otherwise the available services will be listed.

All services can be started by using: <info>%command.full_name% all</info>

Services can have dependencies e.g. a database or other provider or maybe
another service. By default, you will be prompted if you wish to start
dependencies; however you can always start dependencies <info>-d</info> or not
<info>-D</info> and avoid the prompt.

If a dependent service is not installed, you can optionally pass <info>--install</info>
to attempt to download and install those services before starting the
one you specified.

If you need to re-build the containers, add <info>--rebuild</info> or <info>-b</info>.
This will force a re-build without using caches. This does not refresh any
already cached images. If you need to get a newer container image use
<info>--refresh</info> instead. This will cause the remote images to be updated.

Both commands use <info>docker-compose build</info> under-the-hood and will stop
any running containers.

HLP)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->setIsDebugging($input);
        $this->setupConsoleHelper($input, $output);

        $project = $this->getActiveProject();

        $this->tools()->info('starting service(s) in <info>%s</info>', $project->name());

        if ((null !== $service = $project->getServiceByPath(getcwd())) && !$input->getArgument('service')) {
            $services = [$service->name()];
            $this->tools()->info('auto-starting <info>%s</info>', $service->name());
        } else {
            $services = $this->getServicesFrom($input, 'starting all services, this might take a while...', 'Select the services to start: ');
        }

        foreach ($services as $name) {
            $this->startService($name);
        }

        if ($input->getOption('validate')) {
            foreach ($services as $name) {
                $this->validate($name, (int)$input->getOption('timeout'));
            }
        }

        return 0;
    }

    private function startService(string $serviceName): void
    {
        $project = $this->getActiveProject();

        if (null === $service = $project->services()->get($serviceName)) {
            /** @var Service $service */
            $this->tools()->error('service <info>%s</info> not found!', $serviceName);
            return;
        }

        if (!$service->isInstalled()) {
            $install = $this->tools()->input()->getOption('install');

            if (!$install) {
                $install = $this->tools()->ask('service <info>%s</info> is not installed, would you like to install it? (y/n) ', false, $service->name());
            }

            if ($install) {
                if (!$this->tools()->execute('spm services:install ' . $service->name(), $project->workingPath())) {
                    $this->tools()->error('failed to install <info>%s</info>, re-run with -vvv to debug', $service->name());
                    return;
                }
            } else {
                $this->tools()->error('service <info>%s</info> is not installed and cannot be started', $service->name());
                return;
            }
        }

        if (!$service->runningContainerId()) {
            $this->docker->resolve($service);
        }

        if ($service->isRunning()) {
            return;
        }

        if ($service->hasDependencies()) {
            $this->handleServiceDependencies($service);
        }

        $command = $this->tools()->input()->getOption('refresh') ? 'refresh' : ($this->tools()->input()->getOption('rebuild') ? 'build' : 'start');

        $this->{$command}($service);

        $this->tools()->when(
            $service->isRunning(),
            'service started <info>successfully</info>',
            'service did not start, re-run with <info>-vvv</info> or use <info>docker-compose</info>'
        );
        $this->tools()->newline();
    }

    private function handleServiceDependencies(Service $service): void
    {
        $deps = null;

        if ($this->tools()->input()->getOption('with-deps')) {
            $deps = 'y';
        }
        if ($this->tools()->input()->getOption('without-deps')) {
            $deps = 'n';
        }
        if (!$deps) {
            $deps = $this->tools()->ask('Service <info>%s</info> has dependencies, do you want these to be started? (y/n) ', false, $service->name());

            $this->tools()->input()->setOption('with-deps', $deps);
        }

        if (strtolower(trim($deps)) === 'y') {
            $service->dependencies()->each(function ($name) {
                $this->startService($name);
            });
        }
    }

    private function refresh(Service $service): void
    {
        $this->tools()->info('attempting to <info>refresh</info> service <info>%s</info> ', $service->name());
        $this->docker->refresh($service);
        $this->docker->start($service);
    }

    private function build(Service $service): void
    {
        $this->tools()->info('attempting to <info>build</info> service <info>%s</info> ', $service->name());
        $this->docker->build($service);
        $this->docker->start($service);
    }

    private function start(Service $service): void
    {
        $this->tools()->info('attempting to <info>start</info> service <info>%s</info> ', $service->name());
        $this->docker->start($service);
    }

    private function validate(string $serviceName, int $timeout): void
    {
        $project = $this->getActiveProject();
        $timeout = min(($timeout < 1 ? 60 : $timeout), 180);
        $i       = 0;

        /** @var Service $service */
        if (null === $service = $project->services()->get($serviceName)) {
            /** @var Service $service */
            $this->tools()->error('service <info>%s</info> not found!', $serviceName);
            return;
        }

        do {
            $this->docker->resolve($service);

            ++$i;
            sleep(1);

        } while (!$service->isRunning() && $i <= $timeout);

        if (!$service->isRunning()) {
            $this->tools()->error('failed to start <info>%s</info> after waiting <info>%s</info> seconds', $service->name(), $timeout);
        }
    }
}
