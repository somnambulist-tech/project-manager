<?php declare(strict_types=1);

namespace Somnambulist\ProjectManager\Commands\Services;

use LogicException;
use Somnambulist\Collection\MutableCollection;
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
use function is_null;
use function sprintf;

/**
 * Class StopCommand
 *
 * @package Somnambulist\ProjectManager\Commands\Services
 * @subpackage Somnambulist\ProjectManager\Commands\Services\StopCommand
 */
class StopCommand extends AbstractCommand implements DockerAwareInterface, ProjectConfigAwareInterface
{

    use GetServicesFromInput;
    use GetCurrentActiveProject;
    use DockerAwareCommand;
    use ProjectConfigAwareCommand;

    protected function configure()
    {
        $this
            ->setName('services:stop')
            ->setAliases(['stop'])
            ->setDescription('Stop the specified service(s), will stop dependent services')
            ->addArgument('service', InputArgument::IS_ARRAY, 'The service(s) to stop or "all"; see <info>services:list</info> for available services')
            ->setHelp(<<<HLP
Stop configured services from anywhere without needing to be in a specific
service folder. Multiple services can be stopped at the same time and any
dependent services will be calculated and added to the list to stop:

    <info>%command.full_name% service1 service2 ...</info>

All services can be stopped by using: <info>%command.full_name% all</info>

If the command is run without arguments and you are in a runnable service
for the current project, it will stop that service (and any dependent
services), otherwise you will be given a list of services that can be
stopped (or all).

HLP)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->setupConsoleHelper($input, $output);
        $this->setIsDebugging($input);

        $project = $this->getActiveProject();

        $this->tools()->info('stopping service(s) in <info>%s</info>', $project->name());

        if ((null !== $service = $project->getServiceByPath(getcwd())) && !$input->getArgument('service')) {
            $services = new MutableCollection($service->name());
            $this->tools()->info('auto-stopping <info>%s</info>', $service->name());
        } else {
            $services = $this->getServicesFrom($input, 'stopping all services, this might take a while...', 'Select the services to stop: ');
        }

        $dependencyTree = $this->buildDependencyTree($project->services()->list());
        $mustStop       = $this->findDependentServices($services);

        $this->tools()->warning('the following services will be stopped: <comment>%s</comment>', $mustStop->implode('</comment>, <comment>'));

        foreach ($dependencyTree as $service) {
            if ($mustStop->contains($service)) {
                $this->stopService($service);
            }
        }

        return 0;
    }

    private function stopService(string $service): void
    {
        if (null !== $service = $this->getActiveProject()->services()->get($service)) {
            /** @var Service $service */
            $this->tools()->info('attempting to stop <info>%s</info>', $service->name());
            $this->docker->stop($service);

            $service->isRunning() ? $this->tools()->error('failed to stop service') : $this->tools()->success('successfully stopped service');

            $this->tools()->newline();
        } else {
            $this->tools()->error('service <info>%s</info> not found!', $service);
        }
    }

    private function findDependentServices(MutableCollection $services): MutableCollection
    {
        foreach ($services as $name) {
            $this->getActiveProject()->services()->list()->each(function (Service $toCheck) use ($services, $name) {
                if ($toCheck->dependencies()->contains($name) && $services->doesNotContain($toCheck->name())) {
                    $services->add($toCheck->name());
                }
            });
        }

        return $services;
    }

    /**
     * @link https://stackoverflow.com/questions/39711720/php-order-array-based-on-elements-dependency
     *
     * @param MutableCollection $services
     *
     * @return MutableCollection
     */
    private function buildDependencyTree(MutableCollection $services): MutableCollection
    {
        $sortedExpressions    = new MutableCollection();
        $resolvedDependencies = new MutableCollection();

        while ($services->count() > $sortedExpressions->count()) {
            $resolvedDependenciesForService = false;
            $alias = $dep = 'undefined';

            /**
             * @var string  $alias
             * @var Service $service
             */
            foreach ($services as $alias => $service) {
                if ($resolvedDependencies->has($alias)) {
                    continue;
                }

                $resolved = true;

                foreach ($service->dependencies() as $dep) {
                    if (!is_null($test = $services->get($dep)) && $test->dependencies()->contains($test)) {
                        throw new LogicException(sprintf('Cyclical dependency detected for service "%s" and "%s"', $alias, $dep));
                    }

                    if (!$resolvedDependencies->has($dep)) {
                        $resolved = false;
                        break;
                    }
                }

                if ($resolved) {
                    $resolvedDependencies->set($alias, true);
                    $sortedExpressions->add($service->name());
                    $resolvedDependenciesForService = true;
                }
            }

            if (!$resolvedDependenciesForService) {
                throw new LogicException(sprintf('Failed to resolve dependency "%s" for service "%s" ', $dep, $alias));
            }
        }

        return $sortedExpressions->reverse();
    }
}
