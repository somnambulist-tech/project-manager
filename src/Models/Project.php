<?php declare(strict_types=1);

namespace Somnambulist\ProjectManager\Models;

use Somnambulist\Collection\FrozenCollection;
use function sprintf;

/**
 * Class Project
 *
 * @package Somnambulist\ProjectManager\Models
 * @subpackage Somnambulist\ProjectManager\Models\Project
 */
final class Project
{

    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $path;

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
     * @var FrozenCollection
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
     * Constructor
     *
     * @param string      $name
     * @param string      $path
     * @param string|null $servicesName
     * @param string|null $librariesName
     * @param string|null $repository
     * @param array       $docker
     */
    public function __construct(string $name, string $path, ?string $servicesName, ?string $librariesName, ?string $repository, array $docker = [])
    {
        $this->name          = $name;
        $this->path          = $path;
        $this->servicesName  = $servicesName;
        $this->librariesName = $librariesName;
        $this->repository    = $repository;
        $this->docker        = new FrozenCollection($docker);

        $this->libraries = new Libraries();
        $this->services  = new Services();
    }

    public function name(): string
    {
        return $this->name;
    }

    public function path(): string
    {
        return $this->path;
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

    public function docker(): FrozenCollection
    {
        return $this->docker;
    }

    public function getFileInProject($filename): string
    {
        return sprintf('%s/%s', $this->path(), $filename);
    }

    public function configFile(): string
    {
        return $this->getFileInProject('project.yaml');
    }
}
