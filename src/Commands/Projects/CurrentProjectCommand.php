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

    protected function configure()
    {
        $this
            ->setName('current')
            ->setAliases(['cur', 'curr'])
            ->setDescription('Displays the current active project')
            ->addArgument('list', InputArgument::OPTIONAL, 'List services and libraries')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->setupConsoleHelper($input, $output);

        $project = $this->getActiveProject();

        $this->tools()->info('active project is <info>%s</info>', $project->name());
        $this->tools()->info('working dir is <info>%s</info>', $project->workingPath());
        $this->tools()->info('config dir is <info>%s</info>', $project->configPath());
        $this->tools()->info('docker prefix is <info>%s</info>', $project->docker()->get('compose_project_name'));
        $this->tools()->newline();

        if ($input->getArgument('list')) {
            $i = 0;
            $this->tools()->info('available libraries');
            $project
                ->libraries()
                ->list()
                ->sortUsing(function (Library $a, Library $b) {
                    if ($a->name() === $b->name()) {
                        return 0;
                    }

                    return $a->name() > $b->name() ? 1 : -1;
                })->each(function (Library $lib, $key) use (&$i) {
                    $this->tools()->step(++$i, $lib->name());
                })
            ;
            $this->tools()->newline();

            $i = 0;
            $this->tools()->info('available services');

            $project
                ->services()
                ->list()
                ->sortUsing(function (Service $a, Service $b) {
                    if ($a->name() === $b->name()) {
                        return 0;
                    }

                    return $a->name() > $b->name() ? 1 : -1;
                })
                ->each(function (Service $lib, $key) use (&$i) {
                    $this->tools()->step(++$i, $lib->name());
                })
            ;
            $this->tools()->newline();
        }

        return 0;
    }
}
