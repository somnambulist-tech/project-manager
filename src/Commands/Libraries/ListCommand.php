<?php declare(strict_types=1);

namespace Somnambulist\ProjectManager\Commands\Libraries;

use Somnambulist\ProjectManager\Commands\AbstractCommand;
use Somnambulist\ProjectManager\Commands\Behaviours\GetCurrentActiveProject;
use Somnambulist\ProjectManager\Commands\Behaviours\ProjectConfigAwareCommand;
use Somnambulist\ProjectManager\Contracts\ProjectConfigAwareInterface;
use Somnambulist\ProjectManager\Models\Library;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\TableCell;
use Symfony\Component\Console\Helper\TableSeparator;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class ListCommand
 *
 * @package Somnambulist\ProjectManager\Commands\Libraries
 * @subpackage Somnambulist\ProjectManager\Commands\Libraries\ListCommand
 */
class ListCommand extends AbstractCommand implements ProjectConfigAwareInterface
{

    use GetCurrentActiveProject;
    use ProjectConfigAwareCommand;

    protected function configure()
    {
        $this
            ->setAliases(['libraries'])
            ->setName('libraries:list')
            ->setDescription('List configured libraries')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->setupConsoleHelper($input, $output);

        $project = $this->getActiveProject();

        $table = new Table($output);
        $table
            ->setHeaderTitle(sprintf('Libraries (project: <fg=yellow;bg=white>%s</>)', $project->name()))
            ->setHeaders([
                'Service', 'Project Directory', 'Installed?',
            ])
        ;

        $project
            ->libraries()
            ->list()
            ->sort(function (Library $a, Library $b) {
                return $a->name() <=> $b->name();
            })
            ->each(function (Library $service) use ($table) {
                $table->addRow([
                    $service->name(),
                    $service->installPath(),
                    $service->isInstalled() ? 'yes' : 'no',
                ]);
            })
        ;

        $table->addRows([
            new TableSeparator(),
            [new TableCell('Create a new library using: <info>libraries:create</info>', ['colspan' => 3])]
        ]);

        $table->render();

        $this->tools()->newline();

        return 0;
    }
}
