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
 * Class GotoLibraryCommand
 *
 * @package    Somnambulist\ProjectManager\Commands
 * @subpackage Somnambulist\ProjectManager\Commands\GotoLibraryCommand
 */
class GotoLibraryCommand extends AbstractCommand implements ProjectConfigAwareInterface
{

    use GetCurrentActiveProject;
    use ProjectConfigAwareCommand;
    use CanSelectLibraryFromInput;

    protected function configure()
    {
        $this
            ->setName('goto')
            ->setDescription('Go to the folder containing the library / service in a terminal')
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

        $script = 'osascript -e \'tell application "Terminal" to activate\' -e \'tell application "Terminal" to do script "cd %s"\'';
        $script = $_SERVER['SOMNAMBULIST_TERMINAL_SCRIPT'] ?? $script;

        $this->tools()->info('opening <info>%s</info> in new terminal', $resource->name());
        $this->tools()->execute(sprintf($script, $resource->installPath()), $resource->installPath());
        $this->tools()->newline();

        return 0;
    }
}
