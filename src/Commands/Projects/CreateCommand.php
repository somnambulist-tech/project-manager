<?php declare(strict_types=1);

namespace Somnambulist\ProjectManager\Commands\Projects;

use Somnambulist\ProjectManager\Commands\AbstractCommand;
use Somnambulist\ProjectManager\Commands\Behaviours\CanInitialiseGitRepository;
use Somnambulist\ProjectManager\Commands\Behaviours\ProjectConfigAwareCommand;
use Somnambulist\ProjectManager\Contracts\ProjectConfigAwareInterface;
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
class CreateCommand extends AbstractCommand implements ProjectConfigAwareInterface
{

    use ProjectConfigAwareCommand;
    use CanInitialiseGitRepository;

    protected function configure()
    {
        $this
            ->setName('project:create')
            ->setAliases(['create'])
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
        $dir  = sprintf('%s/%s/%s', $_SERVER['HOME'], $this->config->projectsDir(), $name);

        if ($git) {
            $ret = $this->createFromGitRepo($git, $cwd, $file);
        } else {
            $ret = $this->createNewLocalProject($cwd, $file, $name, $docker, $git);
        }

        if (!file_exists($dir)) {
            @mkdir($dir, 0755, true);
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
        repository: $git
        working_dir: '\${HOME}/$dir/$name'
        libraries_dirname: ~
        services_dirname: ~

    docker:
        compose_project_name: '$docker'
        network_name: '{$docker}_network'

    libraries:

    services:
    
    templates:
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
        $this->tools()->warning('cloning git repository from <info>%s</info> to <comment>%s</comment>', $git, $cwd);

        if (!$this->tools()->git()->clone(dirname($cwd), $git, $cwd)) {
            $this->tools()->error('failed to checkout project! run again with -vvv to get debugging');

            return 1;
        }

        $this->tools()->success('project checked out successfully');

        if (!file_exists($file)) {
            $this->tools()->error('no configuration was found in <info>%s</info>', $file);

            return 1;
        }

        $name = Yaml::parseFile($file)['somnambulist']['project']['name'];

        $this->tools()->success('enable the project by running: <info>spm use %s</info>', $name);

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
            $this->tools()->error('failed to create config folder <info>%s</info>', $cwd);

            return 1;
        }

        $this->tools()->warning('created configuration directory at <info>%s</info>', $cwd);

        if (false === file_put_contents($file, $this->config($this->config->projectsDir(), $name, $docker, $git))) {
            $this->tools()->error('failed to create file at <info>%s</info>', $file);

            return 1;
        }

        $this->tools()->warning('created configuration file at <info>%s</info>', $file);
        $this->tools()->warning('creating git repository at <info>%s</info>', $cwd);

        if (0 !== $this->initialiseGitRepositoryAt($cwd)) {
            return 1;
        }

        $this->tools()->success('created git repository at <info>%s</info>', $cwd);
        $this->tools()->success('project created successfully, enable the project by running: <info>spm use %s</info>', $name);

        return 0;
    }
}
