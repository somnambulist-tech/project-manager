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
 * Class SetDockerProjectName
 *
 * @package    Somnambulist\ProjectManager\Commands\Config\Options
 * @subpackage Somnambulist\ProjectManager\Commands\Config\Options\SetDockerProjectName
 */
class SetDockerProjectName extends AbstractCommandOption
{

    private $envs = ['.env', '.env.local', '.env.example', '.env.test', '.env.test.local', '.env.docker'];

    public function __construct()
    {
        $this->option      = 'docker:name';
        $this->description = 'Set the docker compose project name';
        $this->scope       = self::SCOPE_PROJECT;
        $this->questions   = [
            'name'   => 'Enter the name to be used as the project prefix:',
            'rename' => 'Do you want services updating with the revised project name? (y/n)',
        ];
    }

    public function run(Project $project, string $library, array $options): CommandOptionResult
    {
        if (!isset($options['name']) || empty($options['name'])) {
            return CommandOptionResult::error('missing a value for <info>name</info>');
        }

        $old = $project->docker()->get('compose_project_name');
        $msg = '';

        $project->docker()->set('compose_project_name', $options['name']);

        if ($old && 'y' === $options['rename']) {
            $project->services()->list()->each(function (Service $service) use ($old, $options) {
                $search  = sprintf('COMPOSE_PROJECT_NAME=%s', $old);
                $replace = sprintf('COMPOSE_PROJECT_NAME=%s', $options['name']);

                foreach ($this->envs as $env) {
                    $file = $service->getFileInProject($env);

                    if (!file_exists($file)) {
                        continue;
                    }

                    file_put_contents($file, str_replace($search, $replace, file_get_contents($file)));
                }

                return true;
            });

            $msg = sprintf('Attempted to update COMPOSE_PROJECT_NAME in: %s', implode(', ', $this->envs));
        }

        return CommandOptionResult::ok($msg);
    }
}
