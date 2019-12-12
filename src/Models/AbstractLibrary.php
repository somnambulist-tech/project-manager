<?php declare(strict_types=1);

namespace Somnambulist\ProjectManager\Models;

use Somnambulist\ProjectManager\Contracts\InstallableResource;
use function file_exists;

/**
 * Class AbstractLibrary
 *
 * @package Somnambulist\ProjectManager\Models
 * @subpackage Somnambulist\ProjectManager\Models\AbstractLibrary
 */
abstract class AbstractLibrary implements InstallableResource
{

    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $directoryName;

    /**
     * @var string
     */
    private $repository;

    /**
     * Constructor
     *
     * @param string $name
     * @param string $dirname
     * @param string $repository
     */
    public function __construct(string $name, string $dirname, string $repository)
    {
        $this->name          = $name;
        $this->directoryName = $dirname;
        $this->repository    = $repository;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function directoryName(): string
    {
        return $this->directoryName;
    }

    public function repository(): string
    {
        return $this->repository;
    }

    abstract public function installPath(): string;

    public function isInstalled(): bool
    {
        return file_exists($this->installPath());
    }
}
