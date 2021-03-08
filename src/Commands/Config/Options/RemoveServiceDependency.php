<?php declare(strict_types=1);

namespace Somnambulist\ProjectManager\Commands\Config\Options;

use Somnambulist\ProjectManager\Commands\Config\AbstractCommandOption;
use Somnambulist\ProjectManager\Commands\Config\CommandOptionResult;
use Somnambulist\ProjectManager\Models\Project;
use Somnambulist\ProjectManager\Models\Service;

/**
 * Class RemoveServiceDependency
 *
 * @package    Somnambulist\ProjectManager\Commands\Config\Options
 * @subpackage Somnambulist\ProjectManager\Commands\Config\Options\RemoveServiceDependency
 */
class RemoveServiceDependency extends AbstractCommandOption
{

    public function __construct()
    {
        $this->option      = 'service:dependency:remove';
        $this->description = 'Remove a dependency from the specified service';
        $this->scope       = self::SCOPE_SERVICES;
        $this->questions   = [
            'deps' => 'Specify dependencies to remove as a comma separated string:',
        ];
    }

    public function run(Project $project, string $library, array $options): CommandOptionResult
    {
        /** @var Service $service */
        $service = $project->services()->get($library);

        foreach ($this->arrayFromString($options['deps']) as $dep) {
            $service->dependencies()->remove($dep);
        }

        return CommandOptionResult::ok();
    }
}
