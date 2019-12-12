<?php declare(strict_types=1);

namespace Somnambulist\ProjectManager\Commands;

use InvalidArgumentException;
use Somnambulist\ProjectManager\Commands\Behaviours\GetProjectFromInput;
use Somnambulist\ProjectManager\Commands\Behaviours\UseEnvironmentTemplate;
use Somnambulist\ProjectManager\Models\Config;
use Somnambulist\ProjectManager\Models\Project;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use function file_exists;
use function file_put_contents;
use function mkdir;
use const DIRECTORY_SEPARATOR;

/**
 * Class SwitchProjectCommand
 *
 * @package    Somnambulist\ProjectManager\Commands
 * @subpackage Somnambulist\ProjectManager\Commands\SwitchProjectCommand
 */
class SwitchProjectCommand extends BaseCommand
{

    use UseEnvironmentTemplate;
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
            ->setName('use')
            ->setDescription('Switch the current project to the one specified')
            ->addArgument('project', InputArgument::OPTIONAL, 'The project name')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->setupConsoleHelper($input, $output);

        $project = $this->getProjectFrom($input);

        $this->tools()->warning('switching active project to <info>%s</info>', $project->name());

        $this->switch($project);

        $this->tools()->success('active project is now <info>%s</info>', $project->name());
        $this->tools()->newline();

        return 0;
    }

    private function switch(Project $project)
    {
        $file = $this->config->home() . DIRECTORY_SEPARATOR . '.env';

        file_put_contents($file, $this->environmentTemplate(
            $project->name(),
            $project->path(),
            $project->librariesName(),
            $project->servicesName())
        );
    }
}
