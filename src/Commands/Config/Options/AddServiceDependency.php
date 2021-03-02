<?php declare(strict_types=1);

namespace Somnambulist\ProjectManager\Commands\Config\Options;

use Somnambulist\ProjectManager\Commands\Config\AbstractOption;
use Somnambulist\ProjectManager\Commands\Config\OptionResult;
use Somnambulist\ProjectManager\Models\Project;
use Somnambulist\ProjectManager\Models\Service;

/**
 * Class AddServiceDependency
 *
 * @package    Somnambulist\ProjectManager\Commands\Config\Options
 * @subpackage Somnambulist\ProjectManager\Commands\Config\Options\AddServiceDependency
 */
class AddServiceDependency extends AbstractOption
{

    public function __construct()
    {
        $this->option      = 'service:dependency:add';
        $this->description = 'Add a dependency to the service';
        $this->scope       = self::SCOPE_SERVICES;
        $this->questions   = [
            'deps' => 'Specify dependencies to add as a comma separated string:',
        ];
    }

    public function run(Project $project, string $library, array $options): OptionResult
    {
        /** @var Service $service */
        $service = $project->services()->get($library);

        foreach ($this->arrayFromString($options['deps']) as $dep) {
            if (!$service->dependencies()->contains($dep)) {
                $service->dependencies()->add($dep);
            }
        }

        return OptionResult::ok();
    }
}
