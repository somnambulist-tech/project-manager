<?php declare(strict_types=1);

namespace Somnambulist\ProjectManager\Commands;

use InvalidArgumentException;
use Somnambulist\ProjectManager\Commands\Behaviours\GetProjectFromInput;
use Somnambulist\ProjectManager\Commands\Behaviours\UseEnvironmentTemplate;
use Somnambulist\ProjectManager\Models\Config;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;
use function file_exists;
use function file_put_contents;
use function mkdir;
use const DIRECTORY_SEPARATOR;

/**
 * Class UpdateProjectCommand
 *
 * @package    Somnambulist\ProjectManager\Commands
 * @subpackage Somnambulist\ProjectManager\Commands\UpdateProjectCommand
 */
class UpdateProjectCommand extends BaseCommand
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
            ->setName('projects:update')
            ->setDescription('Pull the latest configuration updates if using Git')
            ->addArgument('project', InputArgument::OPTIONAL, 'The project name')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->setupConsoleHelper($input, $output);

        $project = $this->getProjectFrom($input);

        $this->tools()->warning('updating project config from configured Git repo', $project);

        $helper = $this->getHelper('process');

        $proc = Process::fromShellCommandline('git pull origin')

        $helper->run($output, $proc);

        $this->tools()->success('active project is now <info>%s</info>', $project);
        $this->tools()->newline();

        return 0;
    }

    private function switch(string $name)
    {
        if (null === $project = $this->config->projects()->get($name)) {
            throw new InvalidArgumentException(sprintf('Project "%s" does not exist or is not configured', $name));
        }

    }
}
