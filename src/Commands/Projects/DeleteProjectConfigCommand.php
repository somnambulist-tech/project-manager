<?php declare(strict_types=1);

namespace Somnambulist\ProjectManager\Commands\Projects;

use Somnambulist\ProjectManager\Commands\AbstractCommand;
use Somnambulist\ProjectManager\Commands\Behaviours\GetProjectFromInput;
use Somnambulist\ProjectManager\Commands\Behaviours\ProjectConfigAwareCommand;
use Somnambulist\ProjectManager\Contracts\ProjectConfigAwareInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class DeleteProjectConfigCommand
 *
 * @package    Somnambulist\ProjectManager\Commands\Projects
 * @subpackage Somnambulist\ProjectManager\Commands\Projects\DeleteProjectConfigCommand
 */
class DeleteProjectConfigCommand extends AbstractCommand implements ProjectConfigAwareInterface
{

    use GetProjectFromInput;
    use ProjectConfigAwareCommand;

    protected function configure()
    {
        $this
            ->setName('project:delete')
            ->setDescription('Remove the configuration for the specified project')
            ->addArgument('project', InputArgument::OPTIONAL, 'The project name')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->setupConsoleHelper($input, $output);

        $project = $this->getProjectFrom($input);

        $this->tools()->warning('this will delete <info>only</info> the configuration!');

        if ('y' !== $this->tools()->ask('are you really sure you wish to remove this project? [y/n] ', false)) {
            $this->tools()->info('delete cancelled');
            $this->tools()->newline();

            return 0;
        }

        $cwd = $project->configPath();

        if (!$this->tools()->execute(sprintf('rm -rf %s', $cwd))) {
            $this->tools()->error('there was an error removing <comment>%s</comment>', $cwd);
            $this->tools()->newline();

            return 1;
        }

        $this->tools()->success('project deleted successfully');
        $this->tools()->newline();

        return 0;
    }
}
