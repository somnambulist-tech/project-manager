<?php declare(strict_types=1);

namespace Somnambulist\ProjectManager\Commands\Projects;

use Somnambulist\ProjectManager\Commands\BaseCommand;
use Somnambulist\ProjectManager\Models\Config;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Yaml;
use function dirname;
use function file_exists;
use function file_put_contents;
use function mkdir;
use function sprintf;

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
            ->setName('project:create')
            ->setDescription('Create a new micro-services project and configuration')
            ->addArgument('name', InputArgument::OPTIONAL, 'Project name, must be unique')
            ->addArgument('docker', InputArgument::OPTIONAL, 'Project Docker compose project name, must be unique')
            ->addArgument('repo', InputArgument::OPTIONAL, 'Project configuration git repository if one already exists')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->setupConsoleHelper($input, $output);

        $name   = $input->getArgument('name');
        $docker = $input->getArgument('docker');
        $git    = $input->getArgument('repo');

        if (!$name) {
            $name = $this->tools()->ask('What is your <comment>projects</comment> name? Use lowercase and underscores: ');
        }
        if (!$git) {
            $git = $this->tools()->ask('What is your <comment>remote git repo</comment> to load config data from? Leave blank to skip: ');
        }
        if (!$git && !$docker) {
            $docker = $this->tools()->ask('What will be your <comment>Docker Compose</comment> name? Use lowercase and hyphens: ');
        }

        $cwd  = sprintf('%s/%s', $this->config->home(), $name);
        $file = sprintf('%s/%s/project.yaml', $this->config->home(), $name);

        if ($git) {
            $ret = $this->createFromGitRepo($git, $cwd, $file);
        } else {
            $ret = $this->createNewLocalProject($cwd, $file, $name, $docker, $git);
        }

        $this->tools()->newline();

        return $ret;
    }

    private function config($dir, $name, $docker, $git)
    {
        $git = $git ?: '~';

        return <<<CFG
somnambulist:
    project:
        name: '$name'
        working_dir: '\${HOME}/$dir/$name'
        repository: $git

    docker:
        compose_project_name: '$docker'

    libraries:

    services:

CFG;
    }

    /**
     * @param string $git
     * @param string $cwd
     * @param string $file
     *
     * @return int
     */
    protected function createFromGitRepo(string $git, string $cwd, string $file): int
    {
        $this->tools()->warning('cloning git repository from <comment>%s</comment> to <comment>%s</comment>', $git, $cwd);

        if (!$this->tools()->execute(sprintf('git clone %s %s', $git, $cwd), dirname($cwd))) {
            $this->tools()->error('failed to checkout project! run again with -vvv to get debugging');

            return 1;
        }

        $this->tools()->success('project checked out successfully');

        if (!file_exists($file)) {
            $this->tools()->error('no configuration was found in <comment>%s</comment>', $file);

            return 1;
        }

        $name = Yaml::parseFile($file)['somnambulist']['project']['name'];

        $this->tools()->success('enable the project by running: <comment>use %s</comment>', $name);

        return 0;
    }

    /**
     * @param string      $cwd
     * @param string      $file
     * @param string      $name
     * @param string      $docker
     * @param string|null $git
     *
     * @return int
     */
    protected function createNewLocalProject(string $cwd, string $file, string $name, string $docker, ?string $git): int
    {
        if (!mkdir($cwd, 0775)) {
            $this->tools()->error('failed to create config folder <comment>%s</comment>', $cwd);

            return 1;
        }

        $this->tools()->warning('created configuration directory at <comment>%s</comment>', $cwd);

        if (false === file_put_contents($file, $this->config($this->config->projectsDir(), $name, $docker, $git))) {
            $this->tools()->error('failed to create file at <comment>%s</comment>', $file);

            return 1;
        }

        $this->tools()->warning('created configuration file at <comment>%s</comment>', $file);
        $this->tools()->warning('creating git repository at <comment>%s</comment>', $cwd);

        $ok = $this->tools()->execute('git init', $cwd);
        $ok = $ok && $this->tools()->execute('git add -A', $cwd);
        $ok = $ok && $this->tools()->execute('git commit -m \'Initial commit\'', $cwd);

        if (!$ok) {
            $this->tools()->error('failed to initialise git repository at <comment>%s</comment>', $cwd);

            return 1;
        }

        $this->tools()->success('created git repository at <comment>%s</comment>', $cwd);
        $this->tools()->success('project created successfully, enable the project by running: <comment>use %s</comment>', $name);

        return 0;
    }
}
