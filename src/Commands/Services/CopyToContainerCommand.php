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
use Somnambulist\ProjectManager\Models\Project;
use Somnambulist\ProjectManager\Models\Service;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use function sprintf;

/**
 * Class CopyToContainerCommand
 *
 * @package    Somnambulist\ProjectManager\Commands\Services
 * @subpackage Somnambulist\ProjectManager\Commands\Services\CopyToContainerCommand
 */
class CopyToContainerCommand extends AbstractCommand implements DockerAwareInterface, ProjectConfigAwareInterface
{

    use DockerAwareCommand;
    use ProjectConfigAwareCommand;
    use GetCurrentActiveProject;
    use CanSelectServiceFromInput;

    protected function configure(): void
    {
        $this
            ->setName('services:copy')
            ->setAliases(['copy', 'cp'])
            ->setDescription('Copy a file to/from the services configured application container')
            ->addArgument('source', InputArgument::REQUIRED, 'The source, either local or the container (service:/path) or folder')
            ->addArgument('target', InputArgument::REQUIRED, 'Where the source should be copied (service:/path) or folder')
            ->setHelp(<<<HLP

Copies a file(s) to/from the application container specified by the service.
To copy into the service, specify using <comment>service_name:/path/to/file</comment>.
The service name will be resolved using the project configuration and the
container resolved to a running container id.

For example; to copy a file named <comment>test.txt</comment> from the current
working directory to the service named <comment>users</comment>:

 * %command.full_name% test.txt users:/app/var/tmp/test.txt

To copy from the service, set the source using the same syntax as the target.
For example, to copy the file <comment>test.txt</comment> from the service named
<comment>users</comment>:

 * %command.full_name% users:/app/var/tmp/test.txt ~/Downloads/test.txt

<i> Note: </i> unlike most commands, the copy is executed in the current working
directory scope not the service installation path. The current working directory:

 * <info>{$_SERVER['PWD']}</info>

HLP)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->setupConsoleHelper($input, $output);

        $project = $this->getActiveProject();
        $source  = $input->getArgument('source');
        $target  = $input->getArgument('target');

        $this->tools()->info('accessing resources in <info>%s</info>', $project->name());

        [$service1, $source] = $this->resolveContainer($project, $source);
        [$service2, $target] = $this->resolveContainer($project, $target);

        if (!$service1 && !$service2) {
            $this->tools()->error('unable to determine the service you requested from the source or target');
            $this->tools()->newline();

            return 1;
        }

        if (!$this->tools()->execute(sprintf('docker cp %s %s', $source, $target))) {
            $this->tools()->error('copy operation failed, the file may not have existed or you do not have permissions to write');
            $this->tools()->info('re-run with <info>-vvv</info> to get debug output');
            $this->tools()->newline();

            return 1;
        }

        $this->tools()->success('file copied successfully');
        $this->tools()->newline();

        return 0;
    }

    private function resolveContainer(Project $project, $string): ?array
    {
        /** @var Service $service */
        if (str_contains($string, ':')) {
            if (null !== $service = $project->services()->get(Str::before($string, ':'))) {
                $this->docker->resolve($service);

                $source = $service->runningContainerId() . ':' . Str::after($string, ':');

                return [$service, $source];
            }
        }

        return [null, $string];
    }
}
