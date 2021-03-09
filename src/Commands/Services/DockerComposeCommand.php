<?php declare(strict_types=1);

namespace Somnambulist\ProjectManager\Commands\Services;

use IlluminateAgnostic\Str\Support\Str;
use Somnambulist\ProjectManager\Commands\AbstractCommand;
use Somnambulist\ProjectManager\Commands\Behaviours\CanSelectServiceFromInput;
use Somnambulist\ProjectManager\Commands\Behaviours\DockerAwareCommand;
use Somnambulist\ProjectManager\Commands\Behaviours\GetCurrentActiveProject;
use Somnambulist\ProjectManager\Commands\Behaviours\ProjectConfigAwareCommand;
use Somnambulist\ProjectManager\Contracts\DockerAwareInterface;
use Somnambulist\ProjectManager\Contracts\ProjectConfigAwareInterface;
use Somnambulist\ProjectManager\Models\Definitions\ServiceDefinition;
use Somnambulist\ProjectManager\Models\Docker\DockerCompose;
use Somnambulist\ProjectManager\Models\Project;
use Somnambulist\ProjectManager\Models\Service;
use Somnambulist\ProjectManager\Services\Docker\ComposeFileDumper;
use Somnambulist\ProjectManager\Services\Docker\ComposeFileLoader;
use Somnambulist\ProjectManager\Services\Docker\Factories\ComposeServiceFactory;
use Somnambulist\ProjectManager\Services\Docker\ServiceDefinitionLocator;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use function dir;
use function dirname;
use function file_exists;
use function file_put_contents;
use function getcwd;
use function str_replace;

/**
 * Class DockerComposeCommand
 *
 * @package    Somnambulist\ProjectManager\Commands\Services
 * @subpackage Somnambulist\ProjectManager\Commands\Services\DockerComposeCommand
 */
class DockerComposeCommand extends AbstractCommand implements DockerAwareInterface, ProjectConfigAwareInterface
{

    use CanSelectServiceFromInput;
    use GetCurrentActiveProject;
    use DockerAwareCommand;
    use ProjectConfigAwareCommand;

    /**
     * @var ServiceDefinitionLocator
     */
    private $locator;

    public function __construct(ServiceDefinitionLocator $locator)
    {
        $this->locator = $locator;

        parent::__construct();
    }

    protected function configure()
    {
        $available = $this->locator->findAll()
            ->map(function (ServiceDefinition $d) { return sprintf('<comment>%s</comment>', $d->name());})
            ->implode("\n")
        ;

        $this
            ->setName('services:docker')
            ->setAliases(['docker'])
            ->setDescription('Adds a Docker container to the current or specified service')
            ->addArgument('service', InputArgument::OPTIONAL, 'The service to add docker containers to')
            ->addArgument('containers', InputArgument::IS_ARRAY, 'The Docker containers to add, see <info>--help</info> for a list')
            ->addOption('config', null, InputOption::VALUE_OPTIONAL, 'The folder to use for any extra files needed by the container', 'config/docker/dev')
            ->addOption('dc', null, InputOption::VALUE_OPTIONAL, 'The docker-compose version to create', '3.7')
            ->setHelp(<<<HLP
Allows adding extra Docker containers to the specified services docker-compose.yml file.

Containers are configured as <comment>definitions</comment>. Several are bundled with
project-manager, however others can be added in your <info>.spm_projects.d</info> config
folder. If this folder is not there, add <info>definitions</info> and then add a YAML
file that contains just the service setup for that container. Additional files can be
added by adding a folder using the same name as the service definition YAML file.

YAML files support parameter extraction and substitution using <info>{SPM::NAME_HERE}</info>.
The following are pre-set with appropriate questions:

<comment>{SPM::NETWORK_NAME}</> the docker network name, taken from the project config
<comment>{SPM::SERVICE_NAME}</>  the name for the container in the docker compose file
<comment>{SPM::EXTERNAL_PORT}</> if set, the exposed port that will be made available on the host
<comment>{SPM::PROJECT_NAME}</>  the current project name, taken from the project config
<comment>{SPM::SERVICE_APP_NAME}</> for nginx / fastcgi: the name of the container to forward to e.g. php-fpm
<comment>{SPM::SERVICE_APP_PORT}</> for nginx / fastcgi: the port of the container to forward to e.g.: 9000
<comment>{SPM::SERVICE_HOST}</> the host name that the container will resolve to (for traefik / proxies)
<comment>{SPM::SERVICE_PORT}</> the internal port the container will run on e.g.: 8080, 3306, 5432

By default the additional files will be written to <info>config/docker/dev</info> in the
services folder. The container name will be used within this folder. This can be defined
by adding <info>--config=</info> relative to the service folder. Any sub-folders will be
preserved and used in the config folder e.g. conf.d/file.conf would be created as
<info>config/docker/dev/NAME/conf.d/file.conf</info>.

The currently available Docker containers that can be added are:

$available

HLP
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->setIsDebugging($input);
        $this->setupConsoleHelper($input, $output);

        $project = $this->getActiveProject();

        $this->tools()->info('modifying service(s) docker-compose in <info>%s</info>', $project->name());

        if ((null === $service = $project->getServiceByPath(getcwd()))) {
            $service = $this->getServiceSelectionFromInput($input, $project);
        }

        if (empty($containers = $input->getArgument('containers'))) {
            $containers = [$this->tools()->choose('Select the docker container to add: ', $this->locator->findAll()->keys()->toArray())];
        }

        $this->tools()->info('adding the following containers: <info>%s</info>', implode(', ', $containers));

        $defs = $this->locator->findAll()->filter(function (ServiceDefinition $def, $key) use ($containers) {
            return in_array($key, $containers);
        });

        if (!$defs->count()) {
            $this->tools()->error('No containers specified; select at least one container to add');

            return 1;
        }

        $loader  = new ComposeFileLoader();
        $dumper  = new ComposeFileDumper();
        $compose = $this->getComposeInstance($service, $loader, $input);

        $defs->each(function (ServiceDefinition $def) use ($project, $service, $compose) {
            $data = $this->getParametersForContainer($project, $def);
            $dc   = (new ComposeServiceFactory())->convert($def->createServiceDefinitionUsing($data));

            $compose->services()->register($data['{SPM::SERVICE_NAME}'], $dc);

            $this->tools()->info('creating any files needed by <info>%s</info>', $def->name());

            $def->files()->each(function (ServiceDefinition $f) use ($service, $data) {
                $path = $this->tools()->input()->getOption('config');
                $file = $service->getFileInProject($p = sprintf('%s/%s/%s', $path, $data['{SPM::SERVICE_NAME}'], $f->name()));

                if (!file_exists(dirname($file))) {
                    @mkdir(dirname($file), 0755, true);
                }

                $this->tools()->when(
                    false !== file_put_contents($file, $f->createServiceDefinitionUsing($data)),
                    'created <info>%s</info> successfully',
                    'failed to make <info>%s</info>, it should be created manually',
                    $p
                );
            });
        });

        $this->tools()->info('writing updated docker-compose.yml file to project service');

        $dumper->store($compose, $service->getFileInProject('docker-compose.yml'));

        $this->tools()->success('done');

        return 0;
    }

    private function getComposeInstance(Service $service, ComposeFileLoader $loader, InputInterface $input): DockerCompose
    {
        if (file_exists($file = $service->getFileInProject('docker-compose.yml'))) {
            $compose = $loader->load($file);
        } else {
            $compose = new DockerCompose($input->getOption('version'));
        }

        return $compose;
    }

    private function getParametersForContainer(Project $project, ServiceDefinition $def): array
    {
        $data = [];

        foreach ($def->parameters() as $parameter) {
            if (null !== $v = $this->getParameterDefaultValue($project, $parameter)) {
                $data[$parameter] = $v;
            } else {
                $data[$parameter] = $this->tools()->ask($this->getParameterQuestionFor($parameter), false);
            }
        }

        return $data;
    }

    private function getParameterDefaultValue(Project $project, string $parameter)
    {
        $d = [
            '{SPM::NETWORK_NAME}' => $project->docker()->get('network_name'),
            '{SPM::PROJECT_NAME}' => $project->name(),
        ];

        return $d[$parameter] ?? null;
    }

    private function getParameterQuestionFor(string $param): string
    {
        $q = [
            '{SPM::NETWORK_NAME}'     => 'Specify the docker network to connect to: ',
            '{SPM::SERVICE_NAME}'     => 'Specify the name for this container in the docker-compose file: ',
            '{SPM::EXTERNAL_PORT}'    => 'Specify the external port to map to this container: ',
            '{SPM::PROJECT_NAME}'     => 'Specify the project-manager project name: ',
            '{SPM::SERVICE_APP_NAME}' => 'Specify the docker-compose container name to connect to: ',
            '{SPM::SERVICE_APP_PORT}' => 'Specify the docker-compose container port to connect to: ',
            '{SPM::SERVICE_HOST}'     => 'Specify the domain / host that the application runs under: ',
            '{SPM::SERVICE_PORT}'     => 'Specify the internal port this container exposes: ',
        ];

        return $q[$param] ?? sprintf('Specify a value for <info>%s</info>: ', Str::lower(Str::title(str_replace(['{SPM::', '}'], '', $param))));
    }
}
