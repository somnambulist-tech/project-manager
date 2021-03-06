<?php declare(strict_types=1);

namespace Somnambulist\ProjectManager\Commands\Config\Options;

use Somnambulist\ProjectManager\Commands\Config\AbstractCommandOption;
use Somnambulist\ProjectManager\Commands\Config\CommandOptionResult;
use Somnambulist\ProjectManager\Models\Project;

/**
 * Class RemoveProjectTemplate
 *
 * @package    Somnambulist\ProjectManager\Commands\Config\Options
 * @subpackage Somnambulist\ProjectManager\Commands\Config\Options\RemoveProjectTemplate
 */
class RemoveProjectTemplate extends AbstractCommandOption
{

    public function __construct()
    {
        $this->option      = 'template:remove';
        $this->description = 'Remove a project template';
        $this->scope       = self::SCOPE_PROJECT;
        $this->questions   = [
            'templates' => 'Remove templates (separate with a comma) from the project:',
        ];
    }

    public function run(Project $project, string $library, array $options): CommandOptionResult
    {
        foreach ($this->arrayFromString($options['templates']) as $template) {
            $project->templates()->list()->unset($template);
        }

        return CommandOptionResult::ok();
    }
}
