<?php declare(strict_types=1);

namespace Somnambulist\ProjectManager\Commands\Config\Options;

use Somnambulist\ProjectManager\Commands\Config\AbstractOption;
use Somnambulist\ProjectManager\Commands\Config\OptionResult;
use Somnambulist\ProjectManager\Models\Project;

/**
 * Class SetDockerNetworkName
 *
 * @package    Somnambulist\ProjectManager\Commands\Config\Options
 * @subpackage Somnambulist\ProjectManager\Commands\Config\Options\SetDockerNetworkName
 */
class SetDockerNetworkName extends AbstractOption
{

    public function __construct()
    {
        $this->option      = 'docker:network';
        $this->description = 'Set the docker shared network name';
        $this->scope       = self::SCOPE_PROJECT;
        $this->questions   = [
            'name' => 'Enter the network name that services communicate with:',
        ];
    }

    public function run(Project $project, string $library, array $options): OptionResult
    {
        if (!isset($options['name']) || empty($options['name'])) {
            return OptionResult::error('missing a value for <info>name</info>');
        }

        $project->docker()->set('network_name', $options['name']);

        return OptionResult::ok();
    }
}
