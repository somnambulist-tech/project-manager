<?php declare(strict_types=1);

namespace Somnambulist\ProjectManager\Commands\Behaviours;

use Somnambulist\ProjectManager\Services\SyncItManager;

/**
 * Trait SyncItAwareCommand
 *
 * @package    Somnambulist\ProjectManager\Commands\Behaviours
 * @subpackage Somnambulist\ProjectManager\Commands\Behaviours\SyncItAwareCommand
 */
trait SyncItAwareCommand
{
    protected ?SyncItManager $syncit = null;

    public function bindSyncItManager(SyncItManager $syncit): void
    {
        $this->syncit = $syncit;
    }
}
