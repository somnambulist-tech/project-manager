<?php declare(strict_types=1);

namespace Somnambulist\ProjectManager\Commands;

use Somnambulist\ProjectManager\Commands\Behaviours\CanSelectLibraryFromInput;
use Somnambulist\ProjectManager\Commands\Behaviours\GetCurrentActiveProject;
use Somnambulist\ProjectManager\Commands\Behaviours\ProjectConfigAwareCommand;
use Somnambulist\ProjectManager\Contracts\ProjectConfigAwareInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class OpenLibraryCommand
 *
 * @package    Somnambulist\ProjectManager\Commands
 * @subpackage Somnambulist\ProjectManager\Commands\OpenLibraryCommand
 */
class OpenLibraryCommand extends AbstractCommand implements ProjectConfigAwareInterface
{

    use GetCurrentActiveProject;
    use ProjectConfigAwareCommand;
    use CanSelectLibraryFromInput;

    protected function configure()
    {
        $this
            ->setName('open')
            ->setDescription('Open the specified library / service in the configured IDE')
            ->addArgument('library', InputArgument::OPTIONAL, 'The name of the library or service')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->setupConsoleHelper($input, $output);

        $project = $this->getActiveProject();

        $this->tools()->info('active project is <info>%s</info>', $project->name());

        if (null === $resource = $this->getLibrarySelectionFromInput($input, $project)) {
            return 1;
        }

        $program = $_SERVER['SOMNAMBULIST_EDITOR'] ?? 'phpstorm';

        $this->tools()->info('opening <info>%s</info> in <info>%s</info>', $resource->name(), $program);
        $this->tools()->execute(sprintf('%s %s', $program, $resource->installPath()), $resource->installPath());
        $this->tools()->newline();

        return 0;
    }
}
