<?php declare(strict_types=1);

namespace Somnambulist\ProjectManager\Commands\Libraries;

use Somnambulist\ProjectManager\Commands\AbstractCommand;
use Somnambulist\ProjectManager\Commands\Behaviours\CanCreateLibraryOrServicesFolder;
use Somnambulist\ProjectManager\Commands\Behaviours\CanInitialiseGitRepository;
use Somnambulist\ProjectManager\Commands\Behaviours\CanUpdateProjectConfiguration;
use Somnambulist\ProjectManager\Commands\Behaviours\GetCurrentActiveProject;
use Somnambulist\ProjectManager\Commands\Behaviours\GetTemplateFromProjectOrConfig;
use Somnambulist\ProjectManager\Commands\Behaviours\ProjectConfigAwareCommand;
use Somnambulist\ProjectManager\Contracts\ProjectConfigAwareInterface;
use Somnambulist\ProjectManager\Models\Library;
use Somnambulist\ProjectManager\Services\Installers\ComposerInstaller;
use Somnambulist\ProjectManager\Services\Installers\ConfigTemplateInstaller;
use Somnambulist\ProjectManager\Services\Installers\EmptyLibraryInstaller;
use Somnambulist\ProjectManager\Services\Installers\GitInstaller;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use const DIRECTORY_SEPARATOR;

/**
 * Class CreateLibraryCommand
 *
 * @package    Somnambulist\ProjectManager\Commands\Libraries
 * @subpackage Somnambulist\ProjectManager\Commands\Libraries\CreateLibraryCommand
 */
class CreateLibraryCommand extends AbstractCommand implements ProjectConfigAwareInterface
{

    use GetCurrentActiveProject;
    use GetTemplateFromProjectOrConfig;
    use ProjectConfigAwareCommand;
    use CanInitialiseGitRepository;
    use CanCreateLibraryOrServicesFolder;
    use CanUpdateProjectConfiguration;

    protected function configure()
    {
        $default = $_SERVER['PROJECT_LIBRARIES_DIR'];

        $this
            ->setName('libraries:create')
            ->setAliases(['library:create', 'create:library', 'library:new', 'new:library'])
            ->setDescription('Creates a new library within the currently active project')
            ->addArgument('name', InputArgument::OPTIONAL, 'The name of the library to create or blank to use the wizard')
            ->addArgument('template', InputArgument::OPTIONAL, 'The name of the template to use for scaffolding the library')
            ->setHelp(<<<HLP

The library will be created in: <info>$default</info>

The folder structure is controlled by the settings in the <comment>project.yaml</comment>
file in the project configuration folder. By default, libraries and services are
located in the root project folder. An alternative folder name can be set by
specifying the folder name for <comment>libraries_dirname</comment>.

Note: the library name must be unique as it is the name of the folder.

HLP
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->setupConsoleHelper($input, $output);

        $project  = $this->getActiveProject();
        $name     = $input->getArgument('name');
        $template = $input->getArgument('template');

        $this->tools()->info('Creating new library for <info>%s</info>', $project->name());

        if (!$name) {
            $name = $this->tools()->ask('What will your library be called? This is the local folder name: ');
        }
        if (!$template) {
            $template = $this->tools()->choose('Which template would you like to use?', $this->config->availableTemplates('library'));
        }
        if (1 === $template = $this->getTemplate($template)) {
            return 1;
        }

        $cwd = $_SERVER['PROJECT_LIBRARIES_DIR'] . DIRECTORY_SEPARATOR . $name;

        $project->libraries()->add(new Library($name, $name));

        switch (true):
            case $template->isGitResource():
                return (new GitInstaller($this->tools(), 'library'))->installInto($project, $template, $name, $cwd);
                break;

            case $template->isComposerResource():
                return (new ComposerInstaller($this->tools(), 'library'))->installInto($project, $template, $name, $cwd);
                break;

            case $template->hasResource():
                return (new ConfigTemplateInstaller($this->tools(), 'library'))->installInto($project, $template, $name, $cwd);
                break;

            default:
                return (new EmptyLibraryInstaller($this->tools(), 'library'))->installInto($project, $name, $cwd);
        endswitch;
    }
}
