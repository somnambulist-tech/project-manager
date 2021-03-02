<?php declare(strict_types=1);

namespace Somnambulist\ProjectManager\Commands\Config\Options;

use Somnambulist\ProjectManager\Commands\Config\AbstractOption;
use Somnambulist\ProjectManager\Commands\Config\OptionResult;
use Somnambulist\ProjectManager\Models\Project;

/**
 * Class SetDockerProjectName
 *
 * @package    Somnambulist\ProjectManager\Commands\Config\Options
 * @subpackage Somnambulist\ProjectManager\Commands\Config\Options\SetDockerProjectName
 */
class SetDockerProjectName extends AbstractOption
{

    public function __construct()
    {
        $this->option      = 'docker:name';
        $this->description = 'Set the docker compose project name';
        $this->scope       = self::SCOPE_PROJECT;
        $this->questions   = [
            'name' => 'Enter the name to be used as the project prefix:',
        ];
    }

    public function run(Project $project, string $library, array $options): OptionResult
    {
        if (!isset($options['name']) || empty($options['name'])) {
            return OptionResult::error('missing a value for <info>name</info>');
        }

        $project->docker()->set('compose_project_name', $options['name']);

        return OptionResult::ok();
    }
}
