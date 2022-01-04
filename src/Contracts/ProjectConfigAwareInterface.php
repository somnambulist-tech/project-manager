<?php declare(strict_types=1);

namespace Somnambulist\ProjectManager\Contracts;

use Somnambulist\ProjectManager\Models\Config;

/**
 * Interface ProjectConfigAwareInterface
 *
 * @package    Somnambulist\ProjectManager\Contracts
 * @subpackage Somnambulist\ProjectManager\Contracts\ProjectConfigAwareInterface
 */
interface ProjectConfigAwareInterface
{
    public function bindConfiguration(Config $config): void;
}
