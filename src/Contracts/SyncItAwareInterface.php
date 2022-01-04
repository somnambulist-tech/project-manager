<?php declare(strict_types=1);

namespace Somnambulist\ProjectManager\Contracts;

use Somnambulist\ProjectManager\Services\SyncItManager;

/**
 * Interface SyncItAwareInterface
 *
 * @package    Somnambulist\ProjectManager\Contracts
 * @subpackage Somnambulist\ProjectManager\Contracts\SyncItAwareInterface
 */
interface SyncItAwareInterface
{
    public function bindSyncItManager(SyncItManager $syncIt): void;
}
