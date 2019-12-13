<?php declare(strict_types=1);

namespace Somnambulist\ProjectManager\Commands\Behaviours;

use Somnambulist\ProjectManager\Models\Config;

/**
 * Trait ProjectConfigAwareCommand
 *
 * @package    Somnambulist\ProjectManager\Commands\Behaviours
 * @subpackage Somnambulist\ProjectManager\Commands\Behaviours\ProjectConfigAwareCommand
 */
trait ProjectConfigAwareCommand
{

    /**
     * @var Config
     */
    private $config;

    public function bindConfiguration(Config $config): void
    {
        $this->config = $config;
    }
}
