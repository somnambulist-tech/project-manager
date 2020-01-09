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
            ->addArgument('service', InputArgument::REQUIRED|InputArgument::IS_ARRAY, 'The services to start, or "all"; see <info>services:list</info> for available services')
            ->addOption('rebuild', 'b', InputOption::VALUE_NONE, 'Re-build the containers before starting')
            ->addOption('refresh', 'r', InputOption::VALUE_NONE, 'Refresh the containers before starting; pulls all new images')
            ->addOption('with-deps', 'd', InputOption::VALUE_NONE, 'Start all dependencies without prompting for confirmation')
            ->addOption('without-deps', 'D', InputOption::VALUE_NONE, 'Ignore all dependencies')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->setIsDebugging($input);
        $this->setupConsoleHelper($input, $output);

        $project = $this->getActiveProject();

        $this->tools()->info('starting service(s) in <info>%s</info>', $project->name());

        $services = $this->getServicesFrom($input, 'starting all services, this might take a while...');

        foreach ($services as $name) {
            $this->startService($name);
        }

        return 0;
    }

    private function startService(string $service): void
    {
        $project = $this->getActiveProject();

        if (null === $service = $project->services()->get($service)) {
            /** @var Service $service */
            $this->tools()->error('service <info>%s</info> not found!', $service);
            return;
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
            $deps = $this->tools()->ask('Service %s has dependencies, do you want these to be started? (y/n) ', false, $service->name());

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
}
