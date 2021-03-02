<?php declare(strict_types=1);

namespace Somnambulist\ProjectManager\Commands\Config\Options;

use Somnambulist\ProjectManager\Commands\Config\AbstractOption;
use Somnambulist\ProjectManager\Commands\Config\OptionResult;
use Somnambulist\ProjectManager\Models\Project;
use Somnambulist\ProjectManager\Models\Template;

/**
 * Class AddProjectTemplate
 *
 * @package    Somnambulist\ProjectManager\Commands\Config\Options
 * @subpackage Somnambulist\ProjectManager\Commands\Config\Options\AddProjectTemplate
 */
class AddProjectTemplate extends AbstractOption
{

    public function __construct()
    {
        $this->option      = 'template:add';
        $this->description = 'Change a project template source (see readme for more details on templates)';
        $this->scope       = self::SCOPE_PROJECT;
        $this->questions   = [
            'type'   => 'What type of template is this [library|service]:',
            'name'   => 'What is the name for this template:',
            'source' => 'What is the source for template files e.g. the git repository, composer project or local folder name:',
        ];
    }

    public function run(Project $project, string $library, array $options): OptionResult
    {
        if (!isset($options['type']) || !in_array($options['type'], ['library', 'service'])) {
            return OptionResult::error('<info>type</info> must be either: <info>library</info> or <info>service</info>');
        }
        if (!isset($options['name']) || empty($options['name'])) {
            return OptionResult::error('missing a value for <info>name</info>');
        }
        if (!isset($options['source']) || empty($options['source'])) {
            return OptionResult::error('missing a value for <info>source</info>');
        }

        $project->templates()->add(new Template($options['name'], $options['type'], $options['source']));

        return OptionResult::ok();
    }
}
