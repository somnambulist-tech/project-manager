<?php declare(strict_types=1);

namespace Somnambulist\ProjectManager\Commands\Config;

use Exception;
use Somnambulist\Collection\MutableCollection;
use Somnambulist\ProjectManager\Commands\AbstractCommand;
use Somnambulist\ProjectManager\Commands\Behaviours\CanSelectLibraryFromInput;
use Somnambulist\ProjectManager\Commands\Behaviours\CanUpdateProjectConfiguration;
use Somnambulist\ProjectManager\Commands\Behaviours\GetCurrentActiveProject;
use Somnambulist\ProjectManager\Commands\Behaviours\ProjectConfigAwareCommand;
use Somnambulist\ProjectManager\Contracts\ProjectConfigAwareInterface;
use Somnambulist\ProjectManager\Models\Project;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use function str_replace;

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
    use CanSelectLibraryFromInput;
    use GetCurrentActiveProject;

    /**
     * @var Options
     */
    private $options;

    protected function configure()
    {
        $this->options = new Options();

        $commands = $this->options->describe();

        $this
            ->setName('config')
            ->setDescription('Change a configuration value in the spm or project config')
            ->addArgument('option', InputArgument::OPTIONAL, 'The configuration option to change')
            ->addArgument('library', InputArgument::OPTIONAL, 'The library or service to work on, or <info>project</info> for the project')
            ->setHelp(<<<HLP

The following options can be modified by this command:

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

        if (!$option) {
            $option = $this->tools()->choose('Select the option to change ', $this->options->list());
        }

        $action = $this->options->get($option);
        $action->setConsoleHelper($this->tools());

        if ('noop' === $action->getOption()) {
            $this->tools()->error('the provided option <info>%s</info> is not available', $option);
            $this->tools()->newline();

            return 1;
        }

        if (!$library) {
            $library = str_replace(
                [' (lib)', ' (service)'],
                '',
                $this->tools()->choose('Select the library to modify', $this->getLibraryOptions($project, $option))
            );
        }

        try {
            $data = [];
            $this->tools()->step(1, 'changing option <info>%s</info> of <info>%s</info>', $option, $library);

            if ($action->hasQuestions()) {
                foreach ($action->getQuestions() as $key => $q) {
                    $data[$key] = (string)$this->tools()->ask($q . ' ');
                }
            }

            $result = $action->run($project, $library, $data);

            if ($result->success()) {
                $this->updateProjectConfig($project, 2);
            }

            $this->displayMessages($result, $option);

            return $result->getResult();

        } catch (Exception $e) {
            $this->tools()->error('failed to update <info>%s</info> for <info>%s</info>', $option, $library);
            $this->tools()->error($e->getMessage());
            $this->tools()->newline();

            return 1;
        }
    }

    private function displayMessages(OptionResult $result, string $option): void
    {
        if ($result->getSuccessMessage()) {
            $this->tools()->success($result->getSuccessMessage());
        }
        if ($result->getErrorMessage()) {
            $this->tools()->error($result->getErrorMessage());
        }
        if ($result->getInfoMessage()) {
            $this->tools()->info($result->getInfoMessage());
        }

        if ($result->success() && !$result->getSuccessMessage()) {
            $this->tools()->success('successfully updated <info>%s</info>', $option);
            $this->tools()->newline();
        }
    }

    private function getLibraryOptions(Project $project, string $option): array
    {
        $libs = $this->options->get($option)->getScope();

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
