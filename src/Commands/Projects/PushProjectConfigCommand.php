<?php declare(strict_types=1);

namespace Somnambulist\ProjectManager\Commands\Projects;

use Somnambulist\ProjectManager\Commands\AbstractCommand;
use Somnambulist\ProjectManager\Commands\Behaviours\GetCurrentActiveProject;
use Somnambulist\ProjectManager\Commands\Behaviours\ProjectConfigAwareCommand;
use Somnambulist\ProjectManager\Contracts\ProjectConfigAwareInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class PushProjectConfigCommand
 *
 * @package    Somnambulist\ProjectManager\Commands\Projects
 * @subpackage Somnambulist\ProjectManager\Commands\Projects\PushProjectConfigCommand
 */
class PushProjectConfigCommand extends AbstractCommand implements ProjectConfigAwareInterface
{

    use GetCurrentActiveProject;
    use ProjectConfigAwareCommand;

    protected function configure()
    {
        $this
            ->setName('project:push')
            ->setDescription('Commit any configuration changes and push to the git repository')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->setupConsoleHelper($input, $output);

        $project = $this->getActiveProject();

        $this->tools()->info('working on <info>%s</info>', $project->name());
        $this->tools()->warning('storing all outstanding configuration changes', $project);

        $cwd = $project->configPath();

        if ($this->tools()->git()->isClean($cwd)) {
            $this->tools()->info('there are no changes detected to the configuration files');
            $this->tools()->newline();

            return 0;
        }

        $ok = $this->tools()->git()->add($cwd);
        $ok = $ok && $this->tools()->git()->commit($cwd, 'updating configuration files');

        if ($ok) {
            $this->tools()->success('changed files committed to git\'d');
        } else {
            $this->tools()->error('failed to commit changes to git, re-run with -vvv to debug');
            $this->tools()->info('There may not have been any changed files');
            $this->tools()->newline();

            return 1;
        }

        if (!$this->tools()->git()->hasRemote($cwd)) {
            return $this->success();
        }

        if (!$this->tools()->git()->push($cwd)) {
            $this->tools()->error('failed to push changes, is a remote configured?');
            $this->tools()->info('You may not have write access to the repository, or are out of sync');
            $this->tools()->newline();

            return 1;
        }

        return $this->success();
    }

    private function success(): int
    {
        $this->tools()->success('project changes pushed to remote successfully');
        $this->tools()->newline();

        return 0;
    }
}
