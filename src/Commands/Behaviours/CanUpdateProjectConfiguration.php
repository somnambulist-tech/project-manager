<?php declare(strict_types=1);

namespace Somnambulist\ProjectManager\Commands\Behaviours;

use Somnambulist\ProjectManager\Models\Project;
use Somnambulist\ProjectManager\Services\Config\ExportProjectToYaml;
use Somnambulist\ProjectManager\Services\Console\ConsoleHelper;

/**
 * Trait CanUpdateProjectConfiguration
 *
 * @package    Somnambulist\ProjectManager\Commands\Behaviours
 * @subpackage Somnambulist\ProjectManager\Commands\Behaviours\CanUpdateProjectConfiguration
 *
 * @method ConsoleHelper tools()
 */
trait CanUpdateProjectConfiguration
{

    protected function updateProjectConfig(Project $project, int $step): int
    {
        $this->tools()->step($step, 'updating project configuration');

        (new ExportProjectToYaml())->export($project, $project->configFile());

        $this->tools()->success('project configuration updated successfully');

        return 0;
    }
}
