<?php declare(strict_types=1);

namespace Somnambulist\ProjectManager\Commands\Behaviours;

use Symfony\Component\Console\Input\InputInterface;

/**
 * Trait IsRunningInDebugMode
 *
 * @package Somnambulist\ProjectManager\Commands\Behaviours
 * @subpackage Somnambulist\ProjectManager\Commands\Behaviours\IsRunningInDebugMode
 */
trait IsRunningInDebugMode
{
    protected bool $debug = false;

    protected function setIsDebugging(InputInterface $input): bool
    {
        return $this->debug = (false !== $input->getOption('verbose'));
    }

    protected function isDebug(): bool
    {
        return $this->debug;
    }

    protected function isNotDebug(): bool
    {
        return !$this->isDebug();
    }
}
