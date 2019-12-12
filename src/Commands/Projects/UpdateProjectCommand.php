<?php declare(strict_types=1);

namespace Somnambulist\ProjectManager\Commands\Projects;

use Somnambulist\ProjectManager\Commands\AbstractCommand;
use Somnambulist\ProjectManager\Commands\Behaviours\GetProjectFromInput;
use Somnambulist\ProjectManager\Models\Config;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class UpdateProjectCommand
 *
 * @package    Somnambulist\ProjectManager\Commands\Projects
 * @subpackage Somnambulist\ProjectManager\Commands\Projects\UpdateProjectCommand
 */
class UpdateProjectCommand extends AbstractCommand
{

    use GetProjectFromInput;

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
            ->setName('project:update')
            ->setDescription('Pull the latest configuration updates if using Git')
            ->addArgument('project', InputArgument::OPTIONAL, 'The project name')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->setupConsoleHelper($input, $output);

        $project = $this->getProjectFrom($input);

        $this->tools()->warning('updating project config from configured Git repo', $project);

        if (!$this->tools()->execute('git pull', $project->configPath())) {
            $this->tools()->error('project update failed! Is this %s a git repository?', $project->configFile());
            $this->tools()->newline();

            return 1;
        }

        $this->tools()->success('project successfully updated');
        $this->tools()->newline();

        return 0;
    }
}
