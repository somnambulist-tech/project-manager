<?php declare(strict_types=1);

namespace Somnambulist\ProjectManager\Commands\Services;

use Somnambulist\ProjectManager\Commands\AbstractCommand;
use Somnambulist\ProjectManager\Commands\Behaviours\CanCreateLibraryOrServicesFolder;
use Somnambulist\ProjectManager\Commands\Behaviours\CanInitialiseGitRepository;
use Somnambulist\ProjectManager\Commands\Behaviours\GetCurrentActiveProject;
use Somnambulist\ProjectManager\Commands\Behaviours\GetTemplateFromProjectOrConfig;
use Somnambulist\ProjectManager\Commands\Behaviours\ProjectConfigAwareCommand;
use Somnambulist\ProjectManager\Contracts\ProjectConfigAwareInterface;
use Somnambulist\ProjectManager\Models\Service;
use Somnambulist\ProjectManager\Services\Installers\ComposerInstaller;
use Somnambulist\ProjectManager\Services\Installers\ConfigTemplateInstaller;
use Somnambulist\ProjectManager\Services\Installers\EmptyServiceInstaller;
use Somnambulist\ProjectManager\Services\Installers\GitInstaller;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use const DIRECTORY_SEPARATOR;

/**
 * Class CreateServiceCommand
 *
 * @package    Somnambulist\ProjectManager\Commands\Libraries
 * @subpackage Somnambulist\ProjectManager\Commands\Libraries\CreateServiceCommand
 */
class CreateServiceCommand extends AbstractCommand implements ProjectConfigAwareInterface
{

    use GetCurrentActiveProject;
    use GetTemplateFromProjectOrConfig;
    use ProjectConfigAwareCommand;
    use CanInitialiseGitRepository;
    use CanCreateLibraryOrServicesFolder;

    protected function configure()
    {
        $default = $_SERVER['PROJECT_SERVICES_DIR'];

        $this
            ->setName('services:create')
            ->setAliases(['service:create', 'create:service', 'service:new', 'new:service'])
            ->setDescription('Creates a new service within the currently active project')
            ->addArgument('name', InputArgument::OPTIONAL, 'The name of the service to create or blank to use the wizard')
            ->addArgument('template', InputArgument::OPTIONAL, 'The name of the template to use for scaffolding the service')
            ->addOption('container', 'c', InputOption::VALUE_OPTIONAL, 'Specify the container app name')
            ->addOption('depends', 'd', InputOption::VALUE_IS_ARRAY | InputOption::VALUE_OPTIONAL, 'Specify the services this service will depend on', [])
            ->setHelp(<<<HLP

The service will be created in: <info>$default</info>

The folder structure is controlled by the settings in the <comment>project.yaml</comment>
file in the project configuration folder. By default, libraries and services are
located in the root project folder. An alternative folder name can be set by
specifying the folder name for <comment>services_dirname</comment>.

Note: the service name must be unique as it is the name of the folder.

Templates can pre-configure the service with defaults. These can be:

 * composer projects
 * git repositories
 * folder in the project configuration 

Several defaults are provided, however they can be overridden.

HLP
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->setupConsoleHelper($input, $output);

        $project      = $this->getActiveProject();
        $name         = $input->getArgument('name');
        $template     = $input->getArgument('template');
        $container    = $input->getOption('container');
        $dependencies = $input->getOption('depends');

        $this->tools()->info('creating new service for <info>%s</info>', $project->name());

        if (!$name) {
            $name = $this->tools()->ask('What will your service be called? This is the local folder name: ');
        }
        if (!$template) {
            $template = $this->tools()->choose('Which template would you like to use?', $this->config->availableTemplates('service'));
        }
        if (!$container) {
            $container = $this->tools()->ask('What will be the name of your application container? [e.g: proxy, kibana, example-app] ');
        }
        if (1 === $template = $this->getTemplate($template)) {
            return 1;
        }

        $cwd = $_SERVER['PROJECT_SERVICES_DIR'] . DIRECTORY_SEPARATOR . $name;

        $project->services()->add(new Service($name, $name, null, null, $container, $dependencies));

        switch (true):
            case $template->isGitResource():
                return (new GitInstaller($this->tools(), 'service'))->installInto($project, $template, $name, $cwd);

            case $template->isComposerResource():
                return (new ComposerInstaller($this->tools(), 'service'))->installInto($project, $template, $name, $cwd);

            case $template->hasResource():
                return (new ConfigTemplateInstaller($this->tools(), 'service'))->installInto($project, $template, $name, $cwd);

            default:
                return (new EmptyServiceInstaller($this->tools(), 'service'))->installInto($project, $name, $cwd);
        endswitch;
    }
}
