<?php declare(strict_types=1);

namespace Somnambulist\ProjectManager\Commands\Projects;

use Somnambulist\ProjectManager\Commands\BaseCommand;
use Somnambulist\ProjectManager\Commands\Behaviours\UseConsoleHelper;
use Somnambulist\ProjectManager\Models\Config;
use Somnambulist\ProjectManager\Models\Project;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use function dirname;
use function file_put_contents;
use function sprintf;
use const CURLE_FTP_CANT_GET_HOST;

/**
 * Class CreateCommand
 *
 * @package    Somnambulist\ProjectManager\Commands\Projects
 * @subpackage Somnambulist\ProjectManager\Commands\Projects\CreateCommand
 */
class CreateCommand extends BaseCommand
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
            ->setName('projects:create')
            ->setDescription('Create a new micro-services project and configuration')
            ->addArgument('name', InputArgument::OPTIONAL, 'Project name, must be unique')
            ->addArgument('docker', InputArgument::OPTIONAL, 'Project Docker compose project name, must be unique')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->setupConsoleHelper($input, $output);

        $name   = $input->getArgument('name');
        $docker = $input->getArgument('docker');

        if (!$name) {
            $name = $this->tools()->ask('What is your <comment>projects</comment> name? Use lowercase and underscores: ');
        }
        if (!$docker) {
            $docker = $this->tools()->ask('What will be your <comment>Docker Compose</comment> name? Use lowercase and hypens: ');
        }

        $file = sprintf('%s/%s/project.yaml', $this->config->home(), $name);

        if (!mkdir(dirname($file), 0775)) {
            return 1;
        }

        $this->tools()->warning('created configuration directory at <comment>%s</comment>', dirname($file));

        file_put_contents($file, $this->config($this->config->projectsDir(), $name, $docker));

        $this->tools()->warning('created configuration file at <comment>%s</comment>', $file);
        $this->tools()->success('project created successfully, enable the project: <comment>use %s</comment>', $name);

        return 0;
    }

    private function config($dir, $name, $docker)
    {
        return <<<CFG
somnambulist:
    project:
        name: '$name'
        working_dir: '\${HOME}/$dir/$name'
        repository: ~

    docker:
        compose_project_name: '$docker'

    libraries:

    services:

CFG;

    }
}
