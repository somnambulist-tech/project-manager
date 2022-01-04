<?php declare(strict_types=1);

namespace Somnambulist\ProjectManager\Commands\Config\Options;

use Somnambulist\ProjectManager\Commands\Config\AbstractCommandOption;
use Somnambulist\ProjectManager\Commands\Config\CommandOptionResult;
use Somnambulist\ProjectManager\Models\Project;
use Somnambulist\ProjectManager\Models\Service;
use function file_exists;
use function file_get_contents;
use function file_put_contents;
use function str_replace;

/**
 * Class SetDockerNetworkName
 *
 * @package    Somnambulist\ProjectManager\Commands\Config\Options
 * @subpackage Somnambulist\ProjectManager\Commands\Config\Options\SetDockerNetworkName
 */
class SetDockerNetworkName extends AbstractCommandOption
{

    public function __construct()
    {
        $this->option      = 'docker:network';
        $this->description = 'Set the docker shared network name';
        $this->scope       = self::SCOPE_PROJECT;
        $this->questions   = [
            'name'   => 'Enter the network name that services communicate with:',
            'rename' => 'Do you want services updating with the revised network name? (y/n)',
        ];
    }

    public function run(Project $project, string $library, array $options): CommandOptionResult
    {
        if (empty($options['name'])) {
            return CommandOptionResult::error('missing a value for <info>name</info>');
        }

        $msg = '';
        $old = $project->docker()->get('network_name');

        $project->docker()->set('network_name', $options['name']);

        if ($old && 'y' === $options['rename']) {
            $project->services()->list()->each(function (Service $service) use ($old, $options) {
                $file = $service->getFileInProject('docker-compose.yml');

                if (!file_exists($file)) {
                    return true;
                }

                $def = file_get_contents($file);

                file_put_contents($file, str_replace($old, $options['name'], $def));

                return true;
            });

            $msg = 'Attempted to update the network reference in all services docker-compose.yml files';
        }

        return CommandOptionResult::ok($msg);
    }
}
