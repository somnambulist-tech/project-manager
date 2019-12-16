<?php declare(strict_types=1);

namespace Somnambulist\ProjectManager\Models;

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

    /**
     * Constructor
     *
     * @param string      $name
     * @param string      $configPath
     * @param string      $workingPath
     * @param string|null $servicesName
     * @param string|null $librariesName
     * @param string|null $repository
     * @param array       $docker
     */
    public function __construct(string $name, string $configPath, string $workingPath, ?string $servicesName, ?string $librariesName, ?string $repository, array $docker = [])
    {
        $this->name          = $name;
        $this->configPath    = $configPath;
        $this->workingPath   = $workingPath;
        $this->servicesName  = $servicesName;
        $this->librariesName = $librariesName;
        $this->repository    = $repository;
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



    public function getFileInProject($filename): string
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
            ->sortByValue()
            ->values()
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
}
