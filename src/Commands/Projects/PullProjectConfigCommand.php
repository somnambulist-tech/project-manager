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

    protected function configure()
    {
        $this
            ->setName('project:pull')
            ->setDescription('Pull the latest configuration updates if using Git')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->setupConsoleHelper($input, $output);

        $project = $this->getActiveProject();

        $this->tools()->info('working on <i>%s</i>', $project->name());
        $this->tools()->warning('updating project config from configured Git repo', $project);

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
