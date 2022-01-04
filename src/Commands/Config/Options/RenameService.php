<?php declare(strict_types=1);

namespace Somnambulist\ProjectManager\Commands\Config\Options;

use Somnambulist\ProjectManager\Commands\Config\AbstractCommandOption;
use Somnambulist\ProjectManager\Commands\Config\CommandOptionResult;
use Somnambulist\ProjectManager\Models\Project;
use Somnambulist\ProjectManager\Models\Service;

/**
 * Class RenameService
 *
 * @package    Somnambulist\ProjectManager\Commands\Config\Options
 * @subpackage Somnambulist\ProjectManager\Commands\Config\Options\RenameService
 */
class RenameService extends AbstractCommandOption
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

    public function run(Project $project, string $library, array $options): CommandOptionResult
    {
        if (empty($options['name'])) {
            return CommandOptionResult::error('missing a value for <info>name</info>');
        }

        /** @var Service $service */
        $service = $project->services()->get($library);

        $project->services()->list()->unset($library);

        $service->rename($options['name']);

        $project->services()->add($service);

        return CommandOptionResult::ok();
    }
}
