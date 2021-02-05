<?php declare(strict_types=1);

namespace Somnambulist\ProjectManager\Commands\Config;

use Somnambulist\Collection\Contracts\Collection;
use Somnambulist\Collection\MutableCollection;
use Somnambulist\ProjectManager\Commands\AbstractCommand;
use Somnambulist\ProjectManager\Commands\Behaviours\CanSelectLibraryFromInput;
use Somnambulist\ProjectManager\Commands\Behaviours\CanUpdateGitRemoteRepository;
use Somnambulist\ProjectManager\Commands\Behaviours\CanUpdateProjectConfiguration;
use Somnambulist\ProjectManager\Commands\Behaviours\GetCurrentActiveProject;
use Somnambulist\ProjectManager\Commands\Behaviours\ProjectConfigAwareCommand;
use Somnambulist\ProjectManager\Contracts\ProjectConfigAwareInterface;
use Somnambulist\ProjectManager\Models\Project;
use Somnambulist\ProjectManager\Models\Service;
use Somnambulist\ProjectManager\Models\Template;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use function explode;
use function ksort;
use function str_replace;
use function substr_count;

/**
 * Class ChangeConfigCommand
 *
 * @package    Somnambulist\ProjectManager\Commands\Config
 * @subpackage Somnambulist\ProjectManager\Commands\Config\ChangeConfigCommand
 */
class ChangeConfigCommand extends AbstractCommand implements ProjectConfigAwareInterface
{

    use ProjectConfigAwareCommand;
    use CanUpdateProjectConfiguration;
    use CanUpdateGitRemoteRepository;
    use CanSelectLibraryFromInput;
    use GetCurrentActiveProject;

    protected function configure()
    {
        $commands = '';

        ksort($this->options);

        foreach ($this->options as $option => $text) {
            $commands .= sprintf("<info>%- 25s</info> : %s\n", $option, $text);
        }

        $this
            ->setName('config')
            ->setDescription('Change a configuration value in the spm or project config')
            ->addArgument('option', InputArgument::OPTIONAL, 'The configuration option to change')
            ->addArgument('library', InputArgument::OPTIONAL, 'The library or service to work or <info>project</info> for the project')
            ->addArgument('value', InputArgument::OPTIONAL | InputArgument::IS_ARRAY, 'The value to set for the option')
            ->setHelp(<<<HLP

The following options can be set by this command:

$commands
HLP
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->setupConsoleHelper($input, $output);

        $project = $this->getActiveProject();

        $option  = $input->getArgument('option');
        $library = $input->getArgument('library');
        $values  = $input->getArgument('value');

        if (!$option) {
            $option = $this->tools()->choose('Select the option to change ', $this->options);
        }
        if (!$library) {
            $library = str_replace(
                [' (lib)', ' (service)'],
                '',
                $this->tools()->choose('Select the library to modify', $this->getLibraryOptions($project, $option))
            );
        }
        if (!$values) {
            $question = $this->valueQuestion[$option] ?? 'Enter the value to set (separate multiple with a comma): ';

            $values = array_filter(array_map('trim', explode(',', (string)$this->tools()->ask($question))));
        }

        if (null === $action = $this->getAction($option)) {
            $this->tools()->error('the provided option <info>%s</info> has no action', $option);
            $this->tools()->newline();

            return 1;
        }

        $this->tools()->step(1, 'changing option <info>%s</info> of <info>%s</info>', $option, $library);
        if (!$this->{$action}($project, $library, $values)) {
            $this->tools()->error('failed to update <info>%s</info> for <info>%s</info>', $option, $library);
            $this->tools()->newline();

            return 1;
        }

        $this->updateProjectConfig($project, 2);

        $this->tools()->success('successfully updated <info>%s</info>', $option);
        $this->tools()->newline();

        return 0;
    }

    private function setGitRemoteRepository(Project $project, string $library, array $values): bool
    {
        $cwd = $resource = null;

        if ('project' === $library) {
            $resource = $project;
            $cwd      = $project->configPath();
        }

        if (!$resource && null === $resource = $project->getLibrary($library)) {
            return false;
        }

        if (!$cwd) {
            $cwd = $resource->installPath();
        }

        $resource->setRepository($values[0]);

        $this->changeGitOrigin($project, $cwd, $values[0]);

        return true;
    }

    private function setDockerName(Project $project, string $library, array $values): bool
    {
        if (!isset($values[0]) || empty($values[0])) {
            return false;
        }

        $project->docker()->set('compose_project_name', $values[0]);

        return true;
    }

    private function setDockerNetwork(Project $project, string $library, array $values): bool
    {
        if (!isset($values[0]) || empty($values[0])) {
            return false;
        }

        $project->docker()->set('network_name', $values[0]);

        return true;
    }

    private function setServiceContainer(Project $project, string $library, array $values): bool
    {
        if (!isset($values[0]) || empty($values[0])) {
            return false;
        }

        $project->getLibrary($library)->setAppContainer($values[0]);

        return true;
    }

    private function addServiceDependency(Project $project, string $library, array $values): bool
    {
        /** @var Service $service */
        $service = $project->services()->get($library);

        foreach ($values as $dep) {
            if (!$service->dependencies()->contains($dep)) {
                $service->dependencies()->add($dep);
            }
        }

        return true;
    }

    private function removeServiceDependency(Project $project, string $library, array $values): bool
    {
        /** @var Service $service */
        $service = $project->services()->get($library);

        foreach ($values as $dep) {
            $service->dependencies()->remove($dep);
        }

        return true;
    }

    private function renameService(Project $project, string $library, array $values): bool
    {
        if (!isset($values[0]) || empty($values[0])) {
            return false;
        }

        /** @var Service $service */
        $service = $project->services()->get($library);

        $project->services()->list()->unset($library);

        $service->rename($values[0]);

        $project->services()->add($service);

        return true;
    }

    private function addTemplate(Project $project, string $library, array $values): bool
    {
        foreach ($values as $template) {
            if (substr_count($template, ':') !== 2) {
                continue;
            }

            [$type, $name, $source] = explode(':', $template);

            $project->templates()->add(new Template($name, $type, $source));
        }

        return true;
    }

    private function removeTemplate(Project $project, string $library, array $values): bool
    {
        foreach ($values as $template) {
            $project->templates()->list()->unset($template);
        }

        return true;
    }

    private const GIT_REMOTE                = 'git:remote';
    private const PROJECT_DOCKER_NAME       = 'docker:name';
    private const PROJECT_DOCKER_NETWORK    = 'docker:network';
    private const SERVICE_CONTAINER         = 'service:container:name';
    private const SERVICE_DEPENDENCY_ADD    = 'service:dependency:add';
    private const SERVICE_DEPENDENCY_REMOVE = 'service:dependency:remove';
    private const SERVICE_RENAME            = 'service:rename';
    private const PROJECT_TEMPLATE_ADD      = 'template:add';
    private const PROJECT_TEMPLATE_REMOVE   = 'template:remove';

    private $options = [
        self::GIT_REMOTE                => 'Set the remote repository for the project/library/service',
        self::PROJECT_DOCKER_NAME       => 'Set the docker compose project name',
        self::PROJECT_DOCKER_NETWORK    => 'Set the docker shared network name',
        self::SERVICE_CONTAINER         => 'Change the name of the services main container (used for detection)',
        self::SERVICE_DEPENDENCY_ADD    => 'Add a dependency to the service',
        self::SERVICE_DEPENDENCY_REMOVE => 'Remove a dependency from the service',
        self::SERVICE_RENAME            => 'Rename an existing services alias',
        self::PROJECT_TEMPLATE_ADD      => 'Change a project template source (specify as type:name:source)',
        self::PROJECT_TEMPLATE_REMOVE   => 'Remove a project template',
    ];

    private $valueQuestion = [
        self::GIT_REMOTE                => 'Enter the full remote git address in the form git://: ',
        self::PROJECT_DOCKER_NAME       => 'Enter the name to be used as the project prefix: ',
        self::PROJECT_DOCKER_NETWORK    => 'Enter the network name that services communicate with: ',
        self::SERVICE_CONTAINER         => 'Enter the name of the main container. This must be a valid docker-compose container name: ',
        self::SERVICE_DEPENDENCY_ADD    => 'Specify dependencies to add as a comma separated string: ',
        self::SERVICE_DEPENDENCY_REMOVE => 'Specify dependencies to remove as a comma separated string: ',
        self::SERVICE_RENAME            => 'Enter the new service alias (this is only the name used in spm): ',
        self::PROJECT_TEMPLATE_ADD      => 'Add or set the template source ([library|service]:name:source) : ',
        self::PROJECT_TEMPLATE_REMOVE   => 'Remove templates (separate with a comma) from the project: ',
    ];

    private $services = [
        self::GIT_REMOTE                => 'AllLibraries',
        self::PROJECT_DOCKER_NAME       => 'Project',
        self::PROJECT_DOCKER_NETWORK    => 'Project',
        self::SERVICE_CONTAINER         => 'Services',
        self::SERVICE_DEPENDENCY_ADD    => 'Services',
        self::SERVICE_DEPENDENCY_REMOVE => 'Services',
        self::SERVICE_RENAME            => 'Services',
        self::PROJECT_TEMPLATE_ADD      => 'Project',
        self::PROJECT_TEMPLATE_REMOVE   => 'Project',
    ];

    private $actions = [
        self::GIT_REMOTE                => 'setGitRemoteRepository',
        self::PROJECT_DOCKER_NAME       => 'setDockerName',
        self::PROJECT_DOCKER_NETWORK    => 'setDockerNetwork',
        self::SERVICE_CONTAINER         => 'setServiceContainer',
        self::SERVICE_DEPENDENCY_ADD    => 'addServiceDependency',
        self::SERVICE_DEPENDENCY_REMOVE => 'removeServiceDependency',
        self::SERVICE_RENAME            => 'renameService',
        self::PROJECT_TEMPLATE_ADD      => 'addTemplate',
        self::PROJECT_TEMPLATE_REMOVE   => 'removeTemplate',
    ];

    private function getAction(string $option): ?string
    {
        return $this->actions[$option] ?? null;
    }

    private function getLibraryOptions(Project $project, string $option): array
    {
        $libs = $this->services[$option] ?? 'AllLibraries';

        if ('Project' === $libs) {
            return ['project'];
        }

        return $this->{'get' . $libs}($project)->toArray();
    }

    private function getLibraries(Project $project): MutableCollection
    {
        return $project
            ->libraries()->list()->keys()
            ->map(function ($value) {
                return $value . ' (lib)';
            })
            ->sortBy('value')
            ->values()
        ;
    }

    private function getServices(Project $project): MutableCollection
    {
        return $project
            ->services()->list()->keys()
            ->map(function ($value) {
                return $value . ' (service)';
            })
            ->sortBy('value')
            ->values()
        ;
    }

    private function getAllLibraries(Project $project): MutableCollection
    {
        return $this
            ->getLibraries($project)
            ->merge($this->getServices($project))
            ->prepend('project')
        ;
    }
}
