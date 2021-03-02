<?php declare(strict_types=1);

namespace Somnambulist\ProjectManager\Commands\Config\Options;

use Somnambulist\ProjectManager\Commands\Config\AbstractOption;
use Somnambulist\ProjectManager\Commands\Config\OptionResult;
use Somnambulist\ProjectManager\Models\Project;
use Somnambulist\ProjectManager\Models\Service;

/**
 * Class RenameService
 *
 * @package    Somnambulist\ProjectManager\Commands\Config\Options
 * @subpackage Somnambulist\ProjectManager\Commands\Config\Options\RenameService
 */
class RenameService extends AbstractOption
{

    public function __construct()
    {
        $this->option      = 'service:rename';
        $this->description = 'Rename an existing services alias';
        $this->scope       = self::SCOPE_SERVICES;
        $this->questions   = [
            'name' => 'Enter the new service alias (this is only the name used in spm):',
        ];
    }

    public function run(Project $project, string $library, array $options): OptionResult
    {
        if (!isset($options['name']) || empty($options['name'])) {
            return OptionResult::error('missing a value for <info>name</info>');
        }

        /** @var Service $service */
        $service = $project->services()->get($library);

        $project->services()->list()->unset($library);

        $service->rename($options['name']);

        $project->services()->add($service);

        return OptionResult::ok();
    }
}
