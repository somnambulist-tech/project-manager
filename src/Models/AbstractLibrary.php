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
    public function __construct(
        private string $name,
        private string $directoryName,
        private ?string $repository = null,
        private ?string $branch = null
    ) {
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
