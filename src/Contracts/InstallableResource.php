<?php declare(strict_types=1);

namespace Somnambulist\ProjectManager\Contracts;

/**
 * Interface InstallableResource
 *
 * @package Somnambulist\ProjectManager\Contracts
 * @subpackage Somnambulist\ProjectManager\Contracts\InstallableResource
 */
interface InstallableResource
{

    public function name(): string;

    public function directoryName(): string;

    public function installPath(): string;

    public function repository(): ?string;

    public function branch(): ?string;

    public function isInstalled(): bool;
}
