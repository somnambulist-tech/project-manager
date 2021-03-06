<?php declare(strict_types=1);

namespace Somnambulist\ProjectManager\Models;

use IlluminateAgnostic\Str\Support\Str;
use Somnambulist\Collection\MutableCollection;
use Somnambulist\ProjectManager\Contracts\InstallableResource;
use Somnambulist\ProjectManager\Contracts\TemplatableResource;
use function sprintf;

/**
 * Class Project
 *
 * @package    Somnambulist\ProjectManager\Models
 * @subpackage Somnambulist\ProjectManager\Models\Project
 */
final class Project implements TemplatableResource
{

    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $configPath;

    /**
     * @var string
     */
    private $workingPath;

    /**
     * @var string|null
     */
    private $servicesName;

    /**
     * @var string|null
     */
    private $librariesName;

    /**
     * @var string|null
     */
    private $repository;

    /**
     * @var string|null
     */
    private $branch;

    /**
     * @var MutableCollection
     */
    private $docker;

    /**
     * @var Libraries
     */
    private $libraries;

    /**
     * @var Services
     */
    private $services;

    /**
     * @var Templates
     */
    private $templates;

    public function __construct(string $name, string $configPath, string $workingPath, ?string $servicesName, ?string $librariesName, ?string $repository, ?string $branch, array $docker = [])
    {
        $this->name          = $name;
        $this->configPath    = $configPath;
        $this->workingPath   = $workingPath;
        $this->servicesName  = $servicesName;
        $this->librariesName = $librariesName;
        $this->repository    = $repository;
        $this->branch        = $branch;
        $this->docker        = new MutableCollection($docker);

        $this->libraries = new Libraries();
        $this->services  = new Services();
        $this->templates = new Templates();
    }

    public function name(): string
    {
        return $this->name;
    }

    public function configPath(): string
    {
        return $this->configPath;
    }

    public function workingPath(): string
    {
        return $this->workingPath;
    }

    public function servicesName(): ?string
    {
        return $this->servicesName;
    }

    public function librariesName(): ?string
    {
        return $this->librariesName;
    }

    public function repository(): ?string
    {
        return $this->repository;
    }

    public function branch(): ?string
    {
        return $this->branch;
    }

    public function libraries(): Libraries
    {
        return $this->libraries;
    }

    public function services(): Services
    {
        return $this->services;
    }

    public function templates(): Templates
    {
        return $this->templates;
    }

    public function docker(): MutableCollection
    {
        return $this->docker;
    }



    public function getFileInProject(string $filename): string
    {
        return sprintf('%s/%s', $this->configPath(), $filename);
    }

    public function configFile(): string
    {
        return $this->getFileInProject('project.yaml');
    }

    public function getListOfLibraries(): MutableCollection
    {
        return $this
            ->libraries()->list()->keys()
            ->map(function ($value) { return $value . ' (lib)';})
            ->merge($this->services()->list()->keys()->map(function ($value) { return $value . ' (service)';}))
            ->sortBy('value')
            ->values()
        ;
    }

    public function getServiceByPath(string $path): ?Service
    {
        return $this
            ->services()->list()->filter(function (Service $service) use ($path) {
                return $service->installPath() === $path;
            })
            ->first()
        ;
    }

    public function getServiceByContainerName(string $container): ?Service
    {
        return $this
            ->services()->list()->filter(function (Service $service) use ($container) {
                return Str::contains($container, $service->appContainer());
            })
            ->first()
        ;
    }

    public function getLibrary(string $library): ?InstallableResource
    {
        if (!$resource = $this->services()->get($library)) {
            if (!$resource = $this->libraries()->get($library)) {
                return null;
            }
        }

        return $resource;
    }

    public function setRepository(string $repository): void
    {
        $this->repository = $repository;
    }

    public function setBranch(?string $branch): void
    {
        $this->branch = $branch;
    }
}
