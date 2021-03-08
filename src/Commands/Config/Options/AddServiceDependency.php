<?php declare(strict_types=1);

namespace Somnambulist\ProjectManager\Commands\Config\Options;

use Somnambulist\ProjectManager\Commands\Config\AbstractCommandOption;
use Somnambulist\ProjectManager\Commands\Config\CommandOptionResult;
use Somnambulist\ProjectManager\Models\Project;
use Somnambulist\ProjectManager\Models\Service;

/**
 * Class AddServiceDependency
 *
 * @package    Somnambulist\ProjectManager\Commands\Config\Options
 * @subpackage Somnambulist\ProjectManager\Commands\Config\Options\AddServiceDependency
 */
class AddServiceDependency extends AbstractCommandOption
{

    public function __construct()
    {
        $this->option      = 'service:dependency:add';
        $this->description = 'Add a dependency to the specified service';
        $this->scope       = self::SCOPE_SERVICES;
        $this->questions   = [
            'deps' => 'Specify dependencies to add as a comma separated string:',
        ];
    }

    public function run(Project $project, string $library, array $options): CommandOptionResult
    {
        /** @var Service $service */
        $service = $project->services()->get($library);

        foreach ($this->arrayFromString($options['deps']) as $dep) {
            if (!$service->dependencies()->contains($dep)) {
                $service->dependencies()->add($dep);
            }
        }

        return CommandOptionResult::ok();
    }
}
