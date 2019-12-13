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
 * Class SetLibraryRepositoryCommand
 *
 * @package    Somnambulist\ProjectManager\Commands\Config
 * @subpackage Somnambulist\ProjectManager\Commands\Config\SetLibraryRepositoryCommand
 */
class SetLibraryRepositoryCommand extends AbstractCommand implements ProjectConfigAwareInterface
{

    use GetCurrentActiveProject;
    use ProjectConfigAwareCommand;
    use CanUpdateProjectConfiguration;
    use CanUpdateGitRemoteRepository;

    protected function configure()
    {
        $this
            ->setName('config:library:repository')
            ->setDescription('Set or change the current git remote origin for the specified library')
            ->addArgument('library', InputArgument::REQUIRED, 'The library to change')
            ->addArgument('repository', InputArgument::REQUIRED, 'The remote git repository')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->setupConsoleHelper($input, $output);

        $project = $this->getActiveProject();
        $library = $project->libraries()->get($input->getArgument('library'));

        if (!$library) {
            $this->tools()->error('library <info>%s</info> not found in this project', $input->getArgument('library'));
            $this->tools()->newline();

            return 1;
        }

        $repo = $input->getArgument('repository');
        $cwd  = $library->installPath();

        $library->setRepository($repo);

        return $this->changeGitOrigin($project, $cwd, $repo);
    }
}
