<?php declare(strict_types=1);

namespace Somnambulist\ProjectManager\Commands\Services;

use LogicException;
use Somnambulist\Collection\MutableCollection;
use Somnambulist\ProjectManager\Commands\AbstractCommand;
use Somnambulist\ProjectManager\Commands\Behaviours\DockerAwareCommand;
use Somnambulist\ProjectManager\Commands\Behaviours\GetCurrentActiveProject;
use Somnambulist\ProjectManager\Commands\Behaviours\GetServicesFromInput;
use Somnambulist\ProjectManager\Contracts\DockerAwareInterface;
use Somnambulist\ProjectManager\Models\Service;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use function is_null;
use function sprintf;

/**
 * Class StopCommand
 *
 * @package Somnambulist\ProjectManager\Commands\Services
 * @subpackage Somnambulist\ProjectManager\Commands\Services\StopCommand
 */
class StopCommand extends AbstractCommand implements DockerAwareInterface
{

    use GetServicesFromInput;
    use GetCurrentActiveProject;
    use DockerAwareCommand;

    protected function configure()
    {
        $this
            ->setName('services:stop')
            ->setDescription('Stop the specified service(s), will stop dependent services')
            ->addArgument('service', InputArgument::REQUIRED|InputArgument::IS_ARRAY, 'The service(s) to stop or "all"; see <info>services:list</info> for available services')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->setupConsoleHelper($input, $output);
        $this->setIsDebugging($input);

        $services = $this->getServicesFrom($input, 'stopping all services, this might take a while...');
        $project  = $this->getActiveProject();

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
        /** @var Service $service */
        if (null !== $service = $this->getActiveProject()->services()->get($service)) {
            $this->tools()->info('attempting to stop <comment>%s</comment>', $service->name());
            $this->docker->stop($service);

            $service->isRunning() ? $this->tools()->error('failed to stop service') : $this->tools()->success('successfully stopped service');

            $this->tools()->newline();
        } else {
            $this->tools()->error('service <comment>%s</comment> not found!', $service);
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
                    if (!is_null($test = $services->get($dep)) && $test->getDependencies()->contains($test)) {
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
