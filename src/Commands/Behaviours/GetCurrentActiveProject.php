<?php declare(strict_types=1);

namespace Somnambulist\ProjectManager\Commands\Behaviours;

use InvalidArgumentException;
use Somnambulist\ProjectManager\Models\Config;
use Somnambulist\ProjectManager\Models\Project;

/**
 * Trait GetProjectFromInput
 *
 * @package    Somnambulist\ProjectManager\Commands\Behaviours
 * @subpackage Somnambulist\ProjectManager\Commands\Behaviours\GetProjectFromInput
 *
 * @property-read Config $config
 */
trait GetCurrentActiveProject
{

    protected function getActiveProject(): Project
    {
        if (null === $project = $this->config->projects()->active()) {
            throw new InvalidArgumentException('There is no active project; run: use <project> to activate one');
        }

        return $project;
    }
}
