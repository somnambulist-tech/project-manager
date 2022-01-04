<?php declare(strict_types=1);

namespace Somnambulist\ProjectManager\Commands;

use IlluminateAgnostic\Str\Support\Str;
use RuntimeException;
use Somnambulist\ProjectManager\Commands\Behaviours\GetCurrentActiveProject;
use Somnambulist\ProjectManager\Commands\Behaviours\ProjectConfigAwareCommand;
use Somnambulist\ProjectManager\Contracts\ProjectConfigAwareInterface;
use Somnambulist\ProjectManager\Models\Project;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Yaml;
use function file_exists;
use function file_get_contents;
use function sprintf;

/**
 * Class PrepareDevEnvironmentCommand
 *
 * @package    Somnambulist\ProjectManager\Commands
 * @subpackage Somnambulist\ProjectManager\Commands\PrepareDevEnvironmentCommand
 */
class PrepareDevEnvironmentCommand extends AbstractCommand implements ProjectConfigAwareInterface
{

    use GetCurrentActiveProject;
    use ProjectConfigAwareCommand;

    protected function configure(): void
    {
        $this
            ->setName('setup:env')
            ->setAliases(['init:mac', 'init:linux', 'init:windows'])
            ->setDescription('Sets up preferred libraries and programs for development as configured in the current project')
            ->addArgument('arch', InputArgument::OPTIONAL, 'The specific config type to run: [mac|linux|windows]', 'mac')
            ->addOption('step', 's', InputOption::VALUE_OPTIONAL, 'Skip to this step in the chain', 0)
            ->addOption('test', 't', InputOption::VALUE_NONE, 'Run a test outputting the commands that will be run')
            ->addOption('bash', 'b', InputOption::VALUE_NONE, 'Output commands on each line without comments; runs as test')
            ->setHelp(<<<HLP
Various commands can be provided in an YAML file for setting up a dev machine
with various applications, configuration defaults for faster dev setup. Typically,
these will be for installing company preferred apps, default configuration or
hosts entries, VPN configuration etc.

By default, this command will attempt to run the <info>mac</info> config options.
You should specify the alternative setup file using the second argument, or use
one of the aliases to pre-configure the command with that architecture.

<comment>Note:</comment> when developing the setup scripts, you should test in a virtual machine
before running the scripts on a live machine.

<info>No attempt is made to auto-detect the running architecture.</info>

HLP)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->setupConsoleHelper($input, $output);

        if (Str::startsWith($comm = $input->getArgument('command'), 'init')) {
            $input->setArgument('arch', Str::after($comm, 'init:'));
        }

        if (!in_array($arch = $input->getArgument('arch'), ['mac', 'linux', 'windows'])) {
            $this->tools()->error('<error>%s</error> is not a valid architecture option. Must be one of: [mac, windows, linux]', $arch);

            return 1;
        }

        $project  = $this->getActiveProject();
        $steps    = $this->loadInitialisationSteps($project, $arch);
        $bashMode = $input->getOption('bash');

        if ($bashMode) {
            $this->tools()->disableOutput();
        }

        $this->tools()->warning('Starting <info>%s</info> preparation', $arch);
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

        foreach ($steps['steps'] as $instructions) {
            $this->tools()
                ->step(++$i, sprintf('%s (<info>%d</info> commands)', $instructions['message'], count($instructions['commands'])))
            ;

            if ($i >= $skip) {
                foreach ($instructions['commands'] as $command) {
                    $toRun = $command['run'];
                    $file  = $command['file'] ?? null;

                    if ('exit' === $toRun) {
                        if (!$bashMode) {
                            $this->tools()->info('<info>%s</info> must wait for the previous command', $comm);
                            $this->tools()->info('resume using: <info>%s -s %s</info>', $comm, $i + 1);
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

                        continue;
                    }

                    if ($this->tools()->execute($command)) {
                        $this->tools()->success('   ...command completed successfully');
                    } else {
                        $this->tools()->error('   ...command failed to execute!');

                        return 1;
                    }
                }
            } else {
                $this->tools()->info('   ...skipping step');
            }
        }

        $this->tools()->success('All steps completed');
        if ('mac' === $arch) {
            $this->tools()->info('If you install Xcode you may wish to run: <info>sudo xcodebuild -license accept</info>');
        }
        $this->tools()->newline();

        return 0;
    }

    private function loadInitialisationSteps(Project $project, string $arch): array
    {
        $this->tools()->info('Reading config data from <info>%s</info> project', $project->name());

        if (!file_exists($file = $project->getFileInProject($f = sprintf('init_%s.yaml', $arch)))) {
            throw new RuntimeException(sprintf('The current project "%s" does not have an "%s" file', $project->name(), $f));
        }

        $config = strtr(file_get_contents($file), [
            '${CONFIG_DIR}' => $project->configPath(),
            '${HOME}'       => $_SERVER['HOME'],
            '${CWD}'        => $_SERVER['PWD'],
        ]);

        return Yaml::parse($config)['somnambulist'] ?? ['steps' => []];
    }
}
