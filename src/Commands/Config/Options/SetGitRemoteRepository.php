<?php declare(strict_types=1);

namespace Somnambulist\ProjectManager\Commands\Config\Options;

use Somnambulist\ProjectManager\Commands\Config\AbstractOption;
use Somnambulist\ProjectManager\Commands\Config\OptionResult;
use Somnambulist\ProjectManager\Exceptions\ResourceNotFound;
use Somnambulist\ProjectManager\Models\Project;

/**
 * Class SetGitRemoteRepository
 *
 * @package    Somnambulist\ProjectManager\Commands\Config\Options
 * @subpackage Somnambulist\ProjectManager\Commands\Config\Options\SetGitRemoteRepository
 */
class SetGitRemoteRepository extends AbstractOption
{

    public function __construct()
    {
        $this->option      = 'git:remote';
        $this->description = 'Set the remote repository for the project/library/service';
        $this->scope       = self::SCOPE_ALL_LIBRARIES;
        $this->questions   = [
            'repo' => 'Enter the full git address in the form git://:',
        ];
    }

    public function run(Project $project, string $library, array $options): OptionResult
    {
        $cwd = $resource = null;

        if (!isset($options['repo']) || empty($options['repo'])) {
            return OptionResult::error('missing a value for <info>repo</info>');
        }

        if ('project' === $library) {
            $resource = $project;
            $cwd      = $project->configPath();
        }

        if (!$resource && null === $resource = $project->getLibrary($library)) {
            throw ResourceNotFound::raise($library);
        }

        if (!$cwd) {
            $cwd = $resource->installPath();
        }

        $resource->setRepository($options['repo']);

        $com = $this->tools->git()->hasRemote($cwd) ? 'setRemote' : 'addRemote';

        $result  = 1;
        $success = $error = $info = '';

        if ($this->tools->git()->{$com}($cwd, 'origin', $options['repo'])) {
            $this->tools->git()->trackRemote($cwd, sprintf('origin/%s', $resource->branch() ?? 'master'));

            $success = sprintf('successfully set <info>origin</info> to <info>%s</info>', $options['repo']);
            $result  = 0;
        } else {
            $error = sprintf('failed to set <info>origin</info> to <info>%s</info>', $options['repo']);
            $info  = sprintf('if you did not use <info>origin</info> as the remote name, manually change it');
        }

        return new OptionResult($result, $success, $error, $info);
    }
}
