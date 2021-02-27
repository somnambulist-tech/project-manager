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
     * @var string|null
     */
    private $branch;

    public function __construct(string $name, string $dirname, string $repository = null, string $branch = null)
    {
        $this->name          = $name;
        $this->directoryName = $dirname;
        $this->repository    = $repository;
        $this->branch        = $branch;
    }

    public function __toString()
    {
        return $this->name();
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

    public function branch(): ?string
    {
        return $this->branch;
    }

    public function setRepository(string $repository): void
    {
        $this->repository = $repository;
    }

    public function setBranch(?string $branch): void
    {
        $this->branch = $branch;
    }

    public function rename(string $name): void
    {
        $this->name = $name;
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
