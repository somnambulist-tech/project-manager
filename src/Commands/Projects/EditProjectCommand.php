<?php declare(strict_types=1);

namespace Somnambulist\ProjectManager\Commands\Projects;

use Somnambulist\ProjectManager\Commands\AbstractCommand;
use Somnambulist\ProjectManager\Commands\Behaviours\GetCurrentActiveProject;
use Somnambulist\ProjectManager\Commands\Behaviours\ProjectConfigAwareCommand;
use Somnambulist\ProjectManager\Contracts\ProjectConfigAwareInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class EditProjectCommand
 *
 * @package    Somnambulist\ProjectManager\Commands\Projects
 * @subpackage Somnambulist\ProjectManager\Commands\Projects\EditProjectCommand
 */
class EditProjectCommand extends AbstractCommand implements ProjectConfigAwareInterface
{

    use GetCurrentActiveProject;
    use ProjectConfigAwareCommand;

    protected function configure(): void
    {
        $this
            ->setName('project:edit')
            ->setAliases(['edit'])
            ->setDescription('Open the config folder with the configured editor for the current project')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->setupConsoleHelper($input, $output);

        $project = $this->getActiveProject();

        $this->tools()->info('active project is <info>%s</info>', $project->name());

        $program = $_SERVER['SOMNAMBULIST_EDITOR'] ?? 'phpstorm';

        $this->tools()->info('opening project configuration in <info>%s</info>', $program);
        $this->tools()->execute(sprintf('%s %s', $program, $project->configPath()), $project->configPath());
        $this->tools()->newline();

        return 0;
    }
}
