<?php declare(strict_types=1);

namespace Somnambulist\ProjectManager\Models;

use Somnambulist\ProjectManager\Contracts\InstallableResource;
use function file_exists;
use function sprintf;

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
     * @var string|null
     */
    private $repository;

    /**
     * Constructor
     *
     * @param string $name
     * @param string $dirname
     * @param string $repository
     */
    public function __construct(string $name, string $dirname, string $repository = null)
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

    public function repository(): ?string
    {
        return $this->repository;
    }

    public function setRepository(string $repository): void
    {
        $this->repository = $repository;
    }

    abstract public function installPath(): string;

    public function isInstalled(): bool
    {
        return file_exists($this->installPath());
    }

    public function getFileInProject($filename): string
    {
        return sprintf('%s/%s', $this->installPath(), $filename);
    }

    public function envFile(): string
    {
        return $this->getFileInProject('.env');
    }
}
