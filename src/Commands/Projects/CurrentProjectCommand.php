<?php declare(strict_types=1);

namespace Somnambulist\ProjectManager\Commands\Projects;

use Somnambulist\ProjectManager\Commands\AbstractCommand;
use Somnambulist\ProjectManager\Commands\Behaviours\GetCurrentActiveProject;
use Somnambulist\ProjectManager\Commands\Behaviours\ProjectConfigAwareCommand;
use Somnambulist\ProjectManager\Contracts\ProjectConfigAwareInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class CurrentProjectCommand
 *
 * @package    Somnambulist\ProjectManager\Commands\Projects
 * @subpackage Somnambulist\ProjectManager\Commands\Projects\CurrentProjectCommand
 */
class CurrentProjectCommand extends AbstractCommand implements ProjectConfigAwareInterface
{

    use GetCurrentActiveProject;
    use ProjectConfigAwareCommand;

    protected function configure()
    {
        $this
            ->setName('current')
            ->setAliases(['cur', 'curr'])
            ->setDescription('Displays the current active project')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->setupConsoleHelper($input, $output);

        if (!$project = $this->getActiveProject()) {
            $this->tools()->error('there is no project currently active');
            $this->tools()->newline();

            return 1;
        }

        $this->tools()->info('active project is <info>%s</info>', $project->name());
        $this->tools()->info('working dir is <info>%s</info>', $project->workingPath());
        $this->tools()->info('config dir is <info>%s</info>', $project->configPath());
        $this->tools()->info('docker prefix is <info>%s</info>', $project->docker()->get('compose_project_name'));
        $this->tools()->newline();

        return 0;
    }
}
