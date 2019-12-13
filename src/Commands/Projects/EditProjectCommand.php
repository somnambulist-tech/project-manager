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

    protected function configure()
    {
        $this
            ->setName('project:edit')
            ->setAliases(['edit'])
            ->setDescription('Open the config folder with PhpStorm for the current project')
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

        $this->tools()->warning('active project is <info>%s</info>', $project->name());

        $this->tools()->info('opening project configuration in PhpStorm');
        $this->tools()->execute(sprintf('phpstorm %s', $project->configPath()), $project->configPath());
        $this->tools()->newline();

        return 0;
    }
}
