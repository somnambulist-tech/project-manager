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

    private function initialiseGitRepositoryAt(string $cwd): int
    {
        $ok = $this->tools()->execute('git init', $cwd);
        $ok = $ok && $this->tools()->execute('git add -A', $cwd);
        $ok = $ok && $this->tools()->execute('git commit -m \'Initial commit\'', $cwd);

        if (!$ok) {
            $this->tools()->error('failed to initialise git repository at <comment>%s</comment>', $cwd);

            return 1;
        }

        return 0;
    }
}
