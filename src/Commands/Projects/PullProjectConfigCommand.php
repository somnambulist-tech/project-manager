<?php declare(strict_types=1);

namespace Somnambulist\ProjectManager\Commands\Projects;

use Somnambulist\ProjectManager\Commands\AbstractCommand;
use Somnambulist\ProjectManager\Commands\Behaviours\GetCurrentActiveProject;
use Somnambulist\ProjectManager\Commands\Behaviours\ProjectConfigAwareCommand;
use Somnambulist\ProjectManager\Contracts\ProjectConfigAwareInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class PullProjectConfigCommand
 *
 * @package    Somnambulist\ProjectManager\Commands\Projects
 * @subpackage Somnambulist\ProjectManager\Commands\Projects\PullProjectConfigCommand
 */
class PullProjectConfigCommand extends AbstractCommand implements ProjectConfigAwareInterface
{

    use GetCurrentActiveProject;
    use ProjectConfigAwareCommand;

    protected function configure(): void
    {
        $this
            ->setName('project:pull')
            ->setAliases(['pull'])
            ->setDescription('Pull the latest configuration updates if using Git')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->setupConsoleHelper($input, $output);

        $project = $this->getActiveProject();

        $this->tools()->info('working on <i>%s</i>', $project->name());

        if (!$this->tools()->git()->hasRemote($project->configPath())) {
            $this->tools()->error('there is no configured remote repository for this project');
            $this->tools()->newline();

            return 1;
        }

        $this->tools()->warning('updating project config from configured Git repo', $project);

        if (!$this->tools()->git()->isClean($project->configPath())) {
            $this->tools()->warning('config has local changes, committing', $project);
            $this->tools()->git()->add($project->configPath());
            $this->tools()->git()->commit($project->configPath());
        }

        if (!$this->tools()->git()->pull($project->configPath())) {
            $this->tools()->error('project update failed! Is this %s a git repository?', $project->configFile());
            $this->tools()->newline();

            return 1;
        }

        $this->tools()->success('project successfully updated');
        $this->tools()->newline();

        return 0;
    }
}
