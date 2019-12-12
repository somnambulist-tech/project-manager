<?php declare(strict_types=1);

namespace Somnambulist\ProjectManager\Commands\Projects;

use Somnambulist\ProjectManager\Commands\AbstractCommand;
use Somnambulist\ProjectManager\Models\Config;
use Somnambulist\ProjectManager\Models\Project;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class ListCommand
 *
 * @package Somnambulist\ProjectManager\Commands\Projects
 * @subpackage Somnambulist\ProjectManager\Commands\Projects\ListCommand
 */
class ListCommand extends AbstractCommand
{

    /**
     * @var Config
     */
    private $config;

    /**
     * Constructor
     *
     * @param Config $config
     */
    public function __construct(Config $config)
    {
        $this->config = $config;

        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setName('project:list')
            ->setDescription('Lists all available, configured projects on this machine')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->setupConsoleHelper($input, $output);

        $table = new Table($output);
        $table
            ->setHeaderTitle(sprintf('Projects (project: <fg=yellow;bg=white>%s</>)', $this->config->active() ?: '-'))
            ->setHeaders([
                'Name', 'Directory', 'Docker Name', '# Libraries', '# Services',
            ])
        ;

        $this->config->projects()->list()->each(function (Project $project) use ($table) {
            $table->addRow([
                $project->name(),
                $project->workingPath(),
                $project->docker()->get('compose_project_name'),
                $project->libraries()->count(),
                $project->services()->count(),
            ]);
        });

        $table->render();

        $this->tools()->newline();

        return 0;
    }
}
