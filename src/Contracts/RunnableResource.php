<?php declare(strict_types=1);

namespace Somnambulist\ProjectManager\Contracts;

use Somnambulist\Collection\MutableCollection;

/**
 * Interface RunnableService
 *
 * @package Somnambulist\ProjectManager\Contracts
 * @subpackage Somnambulist\ProjectManager\Contracts\RunnableService
 */
interface RunnableResource
{

    public function start(string $container): void;

    public function stop(): void;

    public function appContainer(): string;

    public function runningContainerId(): ?string;

    public function dependencies(): MutableCollection;

    public function hasDependencies(): bool;

    public function isRunning(): bool;
}
