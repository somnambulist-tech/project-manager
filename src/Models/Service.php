<?php declare(strict_types=1);

namespace Somnambulist\ProjectManager\Models;

use Somnambulist\Collection\MutableCollection;
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

    /**
     * @var string
     */
    private $appContainer;

    /**
     * @var MutableCollection|string[]
     */
    private $dependencies = [];

    /**
     * @var bool
     */
    private $running = false;

    /**
     * @var null|string
     */
    private $runningContainerId;

    /**
     * Constructor
     *
     * @param string $name
     * @param string $dirname
     * @param string $repository
     * @param string $appContainer
     * @param array  $dependencies
     */
    public function __construct(string $name, string $dirname, ?string $repository, ?string $appContainer, array $dependencies = [])
    {
        parent::__construct($name, $dirname, $repository);

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
