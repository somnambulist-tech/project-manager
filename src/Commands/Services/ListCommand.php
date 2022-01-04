<?php declare(strict_types=1);

namespace Somnambulist\ProjectManager\Commands\Services;

use Somnambulist\ProjectManager\Commands\AbstractCommand;
use Somnambulist\ProjectManager\Commands\Behaviours\DockerAwareCommand;
use Somnambulist\ProjectManager\Commands\Behaviours\GetCurrentActiveProject;
use Somnambulist\ProjectManager\Commands\Behaviours\ProjectConfigAwareCommand;
use Somnambulist\ProjectManager\Contracts\DockerAwareInterface;
use Somnambulist\ProjectManager\Contracts\ProjectConfigAwareInterface;
use Somnambulist\ProjectManager\Models\Service;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\TableCell;
use Symfony\Component\Console\Helper\TableSeparator;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class ListCommand
 *
 * @package    Somnambulist\ProjectManager\Commands\Services
 * @subpackage Somnambulist\ProjectManager\Commands\Services\ListCommand
 */
class ListCommand extends AbstractCommand implements DockerAwareInterface, ProjectConfigAwareInterface
{
    use GetCurrentActiveProject;
    use DockerAwareCommand;
    use ProjectConfigAwareCommand;

    protected function configure(): void
    {
        $this
            ->setAliases(['services'])
            ->setName('services:list')
            ->setDescription('List configured services')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->setupConsoleHelper($input, $output);

        $project = $this->getActiveProject();

        $table = new Table($output);
        $table
            ->setHeaderTitle(sprintf('Services (project: <fg=yellow;bg=white>%s</>)', $project->name()))
            ->setHeaders([
                'Service', 'Project Directory', 'Installed?', 'App Container', 'App Status',
            ])
        ;

        $project
            ->services()
            ->list()
            ->sort(fn(Service $a, Service $b) => $a->name() <=> $b->name())
            ->each(function (Service $service) use ($table) {
                $service->appContainer() ?? $this->docker->resolve($service);

                $table->addRow([
                    $service->name(),
                    $service->installPath(),
                    $service->isInstalled() ? 'yes' : 'no',
                    $service->appContainer(),
                    $service->appContainer() ? $service->isRunning() ? 'running' : 'stopped' : '--',
                ]);
            })
        ;

        $table->addRows([
            new TableSeparator(),
            [new TableCell('Create a new service using: <info>services:create</info>', ['colspan' => 5])],
        ]);

        $table->render();

        $this->tools()->newline();

        return 0;
    }
}
