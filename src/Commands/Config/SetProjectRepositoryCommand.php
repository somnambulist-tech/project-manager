<?php declare(strict_types=1);

namespace Somnambulist\ProjectManager\Commands\Config;

use Somnambulist\ProjectManager\Commands\AbstractCommand;
use Somnambulist\ProjectManager\Commands\Behaviours\CanUpdateGitRemoteRepository;
use Somnambulist\ProjectManager\Commands\Behaviours\CanUpdateProjectConfiguration;
use Somnambulist\ProjectManager\Commands\Behaviours\GetCurrentActiveProject;
use Somnambulist\ProjectManager\Commands\Behaviours\ProjectConfigAwareCommand;
use Somnambulist\ProjectManager\Contracts\ProjectConfigAwareInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class SetProjectRepositoryCommand
 *
 * @package    Somnambulist\ProjectManager\Commands\Config
 * @subpackage Somnambulist\ProjectManager\Commands\Config\SetProjectRepositoryCommand
 */
class SetProjectRepositoryCommand extends AbstractCommand implements ProjectConfigAwareInterface
{

    use GetCurrentActiveProject;
    use ProjectConfigAwareCommand;
    use CanUpdateProjectConfiguration;
    use CanUpdateGitRemoteRepository;

    protected function configure()
    {
        $this
            ->setName('config:project:repository')
            ->setDescription('Set or change the current git remote origin for the current project')
            ->addArgument('repository', InputArgument::REQUIRED, 'The remote git repository')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->setupConsoleHelper($input, $output);

        $project = $this->getActiveProject();
        $cwd     = $project->configPath();
        $repo    = $input->getArgument('repository');

        $project->setRepository($repo);

        return $this->changeGitOrigin($project, $cwd, $repo);
    }
}
