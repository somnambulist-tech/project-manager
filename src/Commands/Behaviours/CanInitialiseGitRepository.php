<?php declare(strict_types=1);

namespace Somnambulist\ProjectManager\Commands\Behaviours;

use Somnambulist\ProjectManager\Services\Console\ConsoleHelper;

/**
 * Trait CanInitialiseGitRepository
 *
 * @package    Somnambulist\ProjectManager\Commands\Behaviours
 * @subpackage Somnambulist\ProjectManager\Commands\Behaviours\CanInitialiseGitRepository
 *
 * @method ConsoleHelper tools()
 */
trait CanInitialiseGitRepository
{

    protected function initialiseGitRepositoryAt(string $cwd): int
    {
        $ok = $this->tools()->git()->init($cwd);
        $ok = $ok && $this->tools()->git()->add($cwd);
        $ok = $ok && $this->tools()->git()->commit($cwd, 'Initial commit');

        if (!$ok) {
            $this->tools()->error('failed to initialise git repository at <info>%s</info>', $cwd);

            return 1;
        }

        return 0;
    }
}
