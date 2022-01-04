<?php declare(strict_types=1);

namespace Somnambulist\ProjectManager\Commands\Config\Options;

use Somnambulist\ProjectManager\Commands\Config\AbstractCommandOption;
use Somnambulist\ProjectManager\Commands\Config\CommandOptionResult;
use Somnambulist\ProjectManager\Models\Project;

/**
 * Class SetServiceContainerName
 *
 * @package    Somnambulist\ProjectManager\Commands\Config\Options
 * @subpackage Somnambulist\ProjectManager\Commands\Config\Options\SetServiceContainerName
 */
class SetServiceContainerName extends AbstractCommandOption
{
    public function __construct()
    {
        $this->option      = 'service:container:name';
        $this->description = 'Change the name of the services main container (used for detection)';
        $this->scope       = self::SCOPE_SERVICES;
        $this->questions   = [
            'name' => 'Enter the name of the main container. This must be a valid docker-compose container name:',
        ];
    }

    public function run(Project $project, string $library, array $options): CommandOptionResult
    {
        if (empty($options['name'])) {
            return CommandOptionResult::error('missing a value for <info>name</info>');
        }

        $project->getLibrary($library)->setAppContainer($options['name']);

        return CommandOptionResult::ok();
    }
}
