<?php declare(strict_types=1);

namespace Somnambulist\ProjectManager\Commands\Behaviours;

use Somnambulist\ProjectManager\Models\Project;
use Somnambulist\ProjectManager\Services\Console\ConsoleHelper;

/**
 * Trait CanUpdateGitRemoteRepository
 *
 * @package    Somnambulist\ProjectManager\Commands\Behaviours
 * @subpackage Somnambulist\ProjectManager\Commands\Behaviours\CanUpdateGitRemoteRepository
 *
 * @method ConsoleHelper tools()
 */
trait CanUpdateGitRemoteRepository
{

    protected function changeGitOrigin(Project $project, $cwd, $repo): int
    {
        $com = 'addRemote';

        if ($this->tools()->git()->hasRemote($cwd)) {
            $com = 'setRemote';
        }

        if ($this->tools()->git()->{$com}($cwd, 'origin', $repo)) {
            $this->updateProjectConfig($project, 1);

            $this->tools()->success('successfully set <info>origin</info> to <info>%s</info>', $repo);
            $this->tools()->newline();

            return 0;
        } else {
            $this->tools()->error('failed to set <info>origin</info> to <info>%s</info>', $repo);
            $this->tools()->info('if you did not use <info>origin</info> as the remote name, manually change it');
            $this->tools()->newline();

            return 1;
        }
    }
}
