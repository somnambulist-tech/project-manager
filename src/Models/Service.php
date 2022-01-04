<?php declare(strict_types=1);

namespace Somnambulist\ProjectManager\Models;

use Somnambulist\Components\Collection\MutableCollection;
use Somnambulist\ProjectManager\Contracts\RunnableResource;
use function sprintf;

/**
 * Class Service
 *
 * @package Somnambulist\ProjectManager\Models
 * @subpackage Somnambulist\ProjectManager\Models\Service
 */
final class Service extends AbstractLibrary implements RunnableResource
{
    private string $appContainer;
    private MutableCollection $dependencies;
    private bool $running = false;
    private ?string $runningContainerId  = null;

    public function __construct(string $name, string $dirname, ?string $repository, ?string $branch, ?string $appContainer, array $dependencies = [])
    {
        parent::__construct($name, $dirname, $repository, $branch);

        $this->appContainer = $appContainer;
        $this->dependencies = new MutableCollection($dependencies);
    }

    public function start(string $container): void
    {
        $this->running            = true;
        $this->runningContainerId = $container;
    }

    public function stop(): void
    {
        $this->running            = false;
        $this->runningContainerId = null;
    }

    public function installPath(): string
    {
        return sprintf('%s/%s', $_SERVER['PROJECT_SERVICES_DIR'], $this->directoryName());
    }

    public function appContainer(): string
    {
        return $this->appContainer;
    }

    public function runningContainerId(): ?string
    {
        return $this->runningContainerId;
    }

    public function dependencies(): MutableCollection
    {
        return $this->dependencies;
    }

    public function hasDependencies(): bool
    {
        return $this->dependencies->count() > 0;
    }

    public function isRunning(): bool
    {
        return $this->running;
    }

    public function setAppContainer(string $appContainer): void
    {
        $this->appContainer = $appContainer;
    }
}
