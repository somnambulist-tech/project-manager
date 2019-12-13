<?php declare(strict_types=1);

namespace Somnambulist\ProjectManager\Commands;

use RuntimeException;
use Somnambulist\ProjectManager\Commands\Behaviours\GetCurrentActiveProject;
use Somnambulist\ProjectManager\Commands\Behaviours\ProjectConfigAwareCommand;
use Somnambulist\ProjectManager\Contracts\ProjectConfigAwareInterface;
use Somnambulist\ProjectManager\Models\Project;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Yaml;
use function file_exists;
use function file_get_contents;
use function sprintf;

/**
 * Class PrepareMacCommand
 *
 * @package Somnambulist\ProjectManager\Commands
 * @subpackage Somnambulist\ProjectManager\Commands\PrepareMacCommand
 */
class PrepareMacCommand extends AbstractCommand implements ProjectConfigAwareInterface
{

    use GetCurrentActiveProject;
    use ProjectConfigAwareCommand;

    protected function configure()
    {
        $this
            ->setName('init:mac')
            ->setDescription('Sets up preferred libraries and programs for development on macOS')
            ->addOption('step', 's', InputOption::VALUE_OPTIONAL, 'Skip to this step in the chain', 0)
            ->addOption('test', 't', InputOption::VALUE_NONE, 'Run a test outputting the commands that will be run')
            ->addOption('bash', 'b', InputOption::VALUE_NONE, 'Output commands on each line without comments; runs as test')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->setupConsoleHelper($input, $output);

        $project  = $this->getActiveProject();
        $steps    = $this->loadInitialisationSteps($project);
        $bashMode = $input->getOption('bash');

        if ($bashMode) {
            $this->tools()->disableOutput();
        }
        
        $this->tools()->warning('Starting macOS preparation');
        $this->tools()->warning('Please note: setup may require sudo for some steps');

        if (!$bashMode) {
            if ('y' !== $this->tools()->ask('Continue with setup? (y|n) ', false)) {
                $this->tools()->info('   ...cancelling setup');
                $this->tools()->newline();

                return 1;
            }
        }

        $this->tools()->newline();

        $i    = 0;
        $skip = $input->getOption('step');

        foreach ($steps['steps'] as $step => $instructions) {
            $this->tools()->step(++$i, sprintf('%s (<info>%d</info> commands)', $instructions['message'], count($instructions['commands'])));

            if ($i >= $skip) {
                foreach ($instructions['commands'] as $command) {
                    $args = null;

                    $toRun = $command['run'];
                    $file  = $command['file'] ?? null;

                    if ('exit' === $toRun) {
                        if (!$bashMode) {
                            $this->tools()->info('init:mac must wait for the previous command');
                            $this->tools()->info('resume using: <comment>init:mac -s %s</comment>', $i + 1);
                            $this->tools()->newline();

                            return 0;
                        }

                        continue;
                    }

                    if ($file) {
                        $toRun = sprintf($toRun, file_get_contents($project->getFileInProject($file)));
                    }

                    if ($input->getOption('test') || $bashMode) {
                        $this->tools()->step('RUN', $toRun);
                        !$bashMode ?: $output->writeln($toRun);
                    } else {
                        if ($this->tools()->execute($command)) {
                            $this->tools()->success('   ...command completed successfully');
                        } else {
                            $this->tools()->error('   ...command failed to execute!');

                            return 1;
                        }
                    }
                }
            } else {
                $this->tools()->info('   ...skipping step');
            }
        }

        $this->tools()->success('All steps completed');
        $this->tools()->info('If you install Xcode you may wish to run: <comment>sudo xcodebuild -license accept</comment>');
        $this->tools()->newline();

        return 0;
    }

    private function loadInitialisationSteps(Project $project): array
    {
        $this->tools()->info('Reading config data from <info>%s</info> project', $project->name());

        if (!file_exists($file = $project->getFileInProject('init_mac.yaml'))) {
            throw new RuntimeException(sprintf('The current project "%s" does not have an "init_mac.yaml" file', $project->name()));
        }

        return Yaml::parseFile($file)['somnambulist'] ?? ['steps' => []];
    }
}
