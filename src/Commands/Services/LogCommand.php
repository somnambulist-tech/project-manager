<?php declare(strict_types=1);

namespace Somnambulist\ProjectManager\Commands\Services;

use Somnambulist\ProjectManager\Commands\AbstractCommand;
use Somnambulist\ProjectManager\Commands\Behaviours\CanSelectServiceFromInput;
use Somnambulist\ProjectManager\Commands\Behaviours\DockerAwareCommand;
use Somnambulist\ProjectManager\Commands\Behaviours\GetCurrentActiveProject;
use Somnambulist\ProjectManager\Commands\Behaviours\ProjectConfigAwareCommand;
use Somnambulist\ProjectManager\Contracts\DockerAwareInterface;
use Somnambulist\ProjectManager\Contracts\ProjectConfigAwareInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

/**
 * Class LogCommand
 *
 * @package    Somnambulist\ProjectManager\Commands\Services
 * @subpackage Somnambulist\ProjectManager\Commands\Services\LogCommand
 */
class LogCommand extends AbstractCommand implements DockerAwareInterface, ProjectConfigAwareInterface
{

    use DockerAwareCommand;
    use ProjectConfigAwareCommand;
    use GetCurrentActiveProject;
    use CanSelectServiceFromInput;

    protected function configure(): void
    {
        $this
            ->setName('services:log')
            ->setAliases(['log', 'logs'])
            ->setDescription('Fetch or tail the application containers log from docker')
            ->addArgument('service', InputArgument::OPTIONAL, 'The service to get the logs of')
            ->addOption('follow', 'f', InputOption::VALUE_NONE, 'Read the logs continuously')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->setupConsoleHelper($input, $output);

        $project = $this->getActiveProject();
        $follow  = $input->getOption('follow');

        $this->tools()->info('accessing logs in <info>%s</info>', $project->name());

        if (null === $service = $this->getServiceSelectionFromInput($input, $project)) {
            return 1;
        }

        $h = $this->getHelper('process');
        $p = Process::fromShellCommandline(sprintf('docker-compose logs %s %s', $follow ? '-f' : '', $service->appContainer()), $service->installPath());
        $p->setTimeout(null);

        $h->run($output, $p, null, function ($type, $data) use ($output) {
            if ('err' === $type) {
                $this->tools()->error($data);
            } else {
                $output->writeln($data);
            }
        });

        return 0;
    }
}
