<?php declare(strict_types=1);

namespace Somnambulist\ProjectManager\Commands\Projects;

use Somnambulist\ProjectManager\Commands\AbstractCommand;
use Somnambulist\ProjectManager\Commands\Behaviours\GetProjectFromInput;
use Somnambulist\ProjectManager\Commands\Behaviours\ProjectConfigAwareCommand;
use Somnambulist\ProjectManager\Contracts\ProjectConfigAwareInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;
use function trim;

/**
 * Class PushProjectConfigCommand
 *
 * @package    Somnambulist\ProjectManager\Commands\Projects
 * @subpackage Somnambulist\ProjectManager\Commands\Projects\PushProjectConfigCommand
 */
class PushProjectConfigCommand extends AbstractCommand implements ProjectConfigAwareInterface
{

    use GetProjectFromInput;
    use ProjectConfigAwareCommand;

    protected function configure()
    {
        $this
            ->setName('project:push')
            ->setDescription('Commit any configuration changes and push to the git repository')
            ->addArgument('project', InputArgument::OPTIONAL, 'The project name')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->setupConsoleHelper($input, $output);

        $project = $this->getProjectFrom($input);

        $this->tools()->warning('storing all outstanding configuration changes', $project);

        $cwd = $project->configPath();

        $proc = Process::fromShellCommandline('git status -s', $cwd);
        $proc->run();
        if (!$res = trim($proc->getOutput())) {
            $this->tools()->info('there are no changes detected to the configuration files');
            $this->tools()->newline();

            return 0;
        }

        $ok = $this->tools()->execute('git add -A', $cwd);
        $ok = $ok && $this->tools()->execute('git commit -m \'updating configuration files\'', $cwd);

        if ($ok) {
            $this->tools()->success('changed files committed to git\'d');
        } else {
            $this->tools()->error('failed to commit changes to git, re-run with -vvv to debug');
            $this->tools()->info('There may not have been any changed files');
            $this->tools()->newline();

            return 1;
        }

        $proc = Process::fromShellCommandline('git remote -v', $cwd);
        $proc->run();
        if (!$res = trim($proc->getOutput())) {
            return $this->success();
        }

        if (!$this->tools()->execute('git push origin master', $cwd)) {
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
