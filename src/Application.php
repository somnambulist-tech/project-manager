<?php declare(strict_types=1);

namespace Somnambulist\ProjectManager;

use Somnambulist\ProjectManager\Contracts\DockerAwareInterface;
use Somnambulist\ProjectManager\Contracts\ProjectConfigAwareInterface;
use Somnambulist\ProjectManager\Contracts\SyncItAwareInterface;
use Somnambulist\ProjectManager\Models\Config;
use Somnambulist\ProjectManager\Services\Console\ConsoleHelper;
use Somnambulist\ProjectManager\Services\DockerManager;
use Somnambulist\ProjectManager\Services\SyncItManager;
use Symfony\Component\Console\Application as BaseApplication;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Command\ListCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Throwable;

/**
 * Class Application
 *
 * Ripped from Symfony FrameworkBundle\Application by Fabien Potencier.
 * Removes all the HTTP setup related features / bundle loading to allow compiled
 * container for a pure CLI app.
 *
 * @package Somnambulist\ProjectManager
 * @subpackage Somnambulist\ProjectManager\Application
 */
class Application extends BaseApplication
{
    private KernelInterface $kernel;
    private bool $commandsRegistered = false;
    private array $registrationErrors = [];

    public function __construct(KernelInterface $kernel, string $version)
    {
        $this->kernel = $kernel;

        parent::__construct('Somnambulist Project Manager', $version);
    }

    public function getLongVersion(): string
    {
        return trim(parent::getLongVersion()) . sprintf(' (project: <comment>%s</>)', $_SERVER['SOMNAMBULIST_ACTIVE_PROJECT'] ?: '-');
    }

    /**
     * Gets the Kernel associated with this Console.
     *
     * @return KernelInterface A KernelInterface instance
     */
    public function getKernel(): KernelInterface
    {
        return $this->kernel;
    }

    public function reset(): void
    {

    }

    /**
     * Runs the current application.
     *
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return int 0 if everything went fine, or an error code
     * @throws Throwable
     */
    public function doRun(InputInterface $input, OutputInterface $output): int
    {
        $this->registerCommands();

        if ($this->registrationErrors) {
            $this->renderRegistrationErrors($input, $output);
        }

        $this->setDispatcher($this->kernel->getContainer()->get('event_dispatcher'));

        return parent::doRun($input, $output);
    }

    protected function doRunCommand(Command $command, InputInterface $input, OutputInterface $output): int
    {
        $helper = new ConsoleHelper($input, $output);

        if ($command instanceof DockerAwareInterface) {
            $docker = $this->kernel->getContainer()->get(DockerManager::class);
            $docker->bindConsoleHelper($helper);

            $command->bindDockerManager($docker);
        }

        if ($command instanceof SyncItAwareInterface) {
            $syncit = $this->kernel->getContainer()->get(SyncItManager::class);
            $syncit->bindConsoleHelper($helper);

            $command->bindSyncItManager($syncit);
        }

        if ($command instanceof ProjectConfigAwareInterface) {
            $command->bindConfiguration($this->kernel->getContainer()->get(Config::class));
        }

        if (!$command instanceof ListCommand) {
            if ($this->registrationErrors) {
                $this->renderRegistrationErrors($input, $output);
                $this->registrationErrors = [];
            }

            return parent::doRunCommand($command, $input, $output);
        }

        $returnCode = parent::doRunCommand($command, $input, $output);

        if ($this->registrationErrors) {
            $this->renderRegistrationErrors($input, $output);
            $this->registrationErrors = [];
        }

        return $returnCode;
    }

    public function find($name): Command
    {
        $this->registerCommands();

        return parent::find($name);
    }

    public function get($name): Command
    {
        $this->registerCommands();

        $command = parent::get($name);

        if ($command instanceof ContainerAwareInterface) {
            $command->setContainer($this->kernel->getContainer());
        }

        return $command;
    }

    public function all($namespace = null): array
    {
        $this->registerCommands();

        return parent::all($namespace);
    }

    public function add(Command $command): Command
    {
        $this->registerCommands();

        return parent::add($command);
    }

    protected function registerCommands(): void
    {
        if ($this->commandsRegistered) {
            return;
        }

        $this->commandsRegistered = true;

        $this->kernel->boot();

        $container = $this->kernel->getContainer();

        if ($container->has('console.command_loader')) {
            $this->setCommandLoader($container->get('console.command_loader'));
        }

        if ($container->hasParameter('console.command.ids')) {
            $lazyCommandIds = $container->hasParameter('console.lazy_command.ids') ? $container->getParameter('console.lazy_command.ids') : [];
            foreach ($container->getParameter('console.command.ids') as $id) {
                if (!isset($lazyCommandIds[$id])) {
                    try {
                        $this->add($container->get($id));
                    } catch (Throwable $e) {
                        $this->registrationErrors[] = $e;
                    }
                }
            }
        }
    }

    private function renderRegistrationErrors(InputInterface $input, OutputInterface $output): void
    {
        if ($output instanceof ConsoleOutputInterface) {
            $output = $output->getErrorOutput();
        }

        (new SymfonyStyle($input, $output))->warning('Some commands could not be registered:');

        foreach ($this->registrationErrors as $error) {
            $this->doRenderThrowable($error, $output);
        }
    }
}
