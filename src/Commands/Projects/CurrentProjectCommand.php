<?php declare(strict_types=1);

namespace Somnambulist\ProjectManager\Commands\Projects;

use Somnambulist\ProjectManager\Commands\AbstractCommand;
use Somnambulist\ProjectManager\Commands\Behaviours\GetCurrentActiveProject;
use Somnambulist\ProjectManager\Commands\Behaviours\ProjectConfigAwareCommand;
use Somnambulist\ProjectManager\Contracts\ProjectConfigAwareInterface;
use Somnambulist\ProjectManager\Models\Library;
use Somnambulist\ProjectManager\Models\Service;
use Symfony\Component\Console\Input\InputArgument;
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

    protected function configure(): void
    {
        $this
            ->setName('current')
            ->setAliases(['cur', 'curr'])
            ->setDescription('Displays the current active project')
            ->addArgument('list', InputArgument::OPTIONAL, 'List services and libraries')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->setupConsoleHelper($input, $output);

        $project = $this->getActiveProject();

        $this->tools()->info('active project is <info>%s</info>', $project->name());
        $this->tools()->info('working dir is <info>%s</info>', $project->workingPath());
        $this->tools()->info('config dir is <info>%s</info>', $project->configPath());
        $this->tools()->info('docker prefix is <info>%s</info>', $project->docker()->get('compose_project_name'));
        $this->tools()->newline();

        if ($input->getArgument('list')) {
            $counter = new class { public int $i = 0; public int $j = 0; };

            $this->tools()->info('available libraries');
            $project
                ->libraries()
                ->list()
                ->sort(fn(Library $a, Library $b) => $a->name() <=> $b->name())
                ->each(fn(Library $lib, $key) => $this->tools()->step(++$counter->i, $lib->name()))
            ;
            $this->tools()->newline();

            $this->tools()->info('available services');
            $project
                ->services()
                ->list()
                ->sort(fn (Service $a, Service $b) => $a->name() <=> $b->name())
                ->each(fn (Service $lib, $key) => $this->tools()->step(++$counter->j, $lib->name()))
            ;
            $this->tools()->newline();
        }

        return 0;
    }
}
