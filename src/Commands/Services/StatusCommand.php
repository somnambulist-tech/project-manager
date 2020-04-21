<?php declare(strict_types=1);

namespace Somnambulist\ProjectManager\Commands\Services;

use Somnambulist\Collection\MutableCollection;
use Somnambulist\ProjectManager\Commands\AbstractCommand;
use Somnambulist\ProjectManager\Commands\Behaviours\DockerAwareCommand;
use Somnambulist\ProjectManager\Commands\Behaviours\GetCurrentActiveProject;
use Somnambulist\ProjectManager\Commands\Behaviours\ProjectConfigAwareCommand;
use Somnambulist\ProjectManager\Commands\Behaviours\SyncItAwareCommand;
use Somnambulist\ProjectManager\Contracts\DockerAwareInterface;
use Somnambulist\ProjectManager\Contracts\ProjectConfigAwareInterface;
use Somnambulist\ProjectManager\Contracts\SyncItAwareInterface;
use Somnambulist\ProjectManager\Models\Project;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\TableCell;
use Symfony\Component\Console\Helper\TableSeparator;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use function array_key_exists;
use function explode;
use function implode;
use function json_encode;
use function parse_url;
use function sprintf;
use function str_replace;
use function strlen;
use function strpos;
use function trim;
use function ucfirst;
use const PHP_URL_HOST;

/**
 * Class StatusCommand
 *
 * @package    Somnambulist\ProjectManager\Commands\Services
 * @subpackage Somnambulist\ProjectManager\Commands\Services\StatusCommand
 */
class StatusCommand extends AbstractCommand implements DockerAwareInterface, ProjectConfigAwareInterface, SyncItAwareInterface
{

    use GetCurrentActiveProject;
    use DockerAwareCommand;
    use ProjectConfigAwareCommand;
    use SyncItAwareCommand;

    protected function configure()
    {
        $this
            ->setName('services:status')
            ->setAliases(['status'])
            ->setDescription('Displays information about the currently running services')
            ->addOption('format', null, InputOption::VALUE_OPTIONAL, 'Optionally format the status into: csv|json|plain|table', 'table')
            ->addOption('no-syncit', null, InputOption::VALUE_NONE, 'Disable querying the SyncIt status')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->setupConsoleHelper($input, $output);

        $project = $this->getActiveProject();
        $format  = in_array($input->getOption('format'), ['table', 'plain', 'json', 'csv']) ? $input->getOption('format') : 'table';
        $data    = $this->fetchStatusData($project, $input->getOption('no-syncit'));

        $this->{'display' . ucfirst($format)}($output, $project, $data);

        return 0;
    }

    private function fetchStatusData(Project $project, bool $disableSyncit = false): MutableCollection
    {
        $data = new MutableCollection();

        $this
            ->docker
            ->status($project->docker()->get('compose_project_name'))
            ->sortUsing(function ($r1, $r2) {
                if ($r1['name'] == $r2['name']) {
                    return 0;
                }

                return $r1['name'] > $r2['name'];
            })
            ->each(function (array $row) use ($data, $project, $disableSyncit) {
                $mounts = $this->getMountsFromString($row['mounts']);
                $ports  = $this->getPortsFromString($row['ports']);
                $labels = $this->getLabelsFromString($row['labels']);
                $host   = $this->getHostFromLabels($labels);
                $syncIt = null;

                if (!$disableSyncit && null !== $service = $project->getServiceByContainerName($row['name'])) {
                    $syncIt = $this->syncit->isRunning($service);
                }

                $data->add([
                    'container'        => $row['name'],
                    'container_status' => explode(' ', $row['status'])[0],
                    'container_host'   => $host,
                    'container_port'   => $ports,
                    'container_mounts' => $mounts,
                    'syncit_status'    => $syncIt,
                ]);
            })
        ;

        return $data;
    }

    private function displayTable(OutputInterface $output, Project $project, MutableCollection $data): void
    {
        $table = new Table($output);
        $table
            ->setHeaderTitle(sprintf('Services Status (project: <fg=yellow;bg=white>%s</>)', $project->name()))
            ->setHeaders(['Running Container Name', 'Status', 'Host', 'Port', 'Mounts', 'SyncIt'])
            ->addRows($data->toArray())
        ;

        if (0 === $data->count()) {
            $table->addRows([
                new TableSeparator(),
                [new TableCell('There are no running services for this project. Start a service using: <info>services:start</info>', ['colspan' => 6])],
            ]);
        }

        $this->tools()->newline();

        $table->render();

        $this->tools()->newline();
    }

    private function displayCsv(OutputInterface $output, Project $project, MutableCollection $data): void
    {
        $output->writeln('"container","container_status","container_host","container_port","container_mounts","syncit_status"');

        $data->each(function ($row) use ($output) {
            $output->writeln(str_replace(["\n"], ["|"], sprintf('"%s"', implode('","', $row))));
        });

        $this->tools()->newline();
    }

    private function displayJson(OutputInterface $output, Project $project, MutableCollection $data): void
    {
        $output->writeln($data->toJson());

        $this->tools()->newline();
    }

    private function displayPlain(OutputInterface $output, Project $project, MutableCollection $data): void
    {
        $output->writeln('container|container_status|container_host|container_port|container_mounts|syncit_status');

        $data->each(function ($row) use ($output) {
            $output->writeln(str_replace(["\n"], [","], sprintf('%s', implode('|', $row))));
        });

        $this->tools()->newline();
    }

    private function getMountsFromString(string $string): string
    {
        $mounts = '';

        if ($string && strlen($string) < 30) {
            $mounts = implode("\n", explode(', ', $string));
        }

        return $mounts;
    }

    private function getPortsFromString(string $string): string
    {
        $ports = '';

        if (false !== strpos($string, '->')) {
            $tmp = [];

            foreach (explode(', ', $string) as $port) {
                if (false !== strpos($port, '->')) {
                    [$forwarded, $internal] = explode('->', $port);

                    $tmp[] = explode(':', $forwarded)[1];
                }
            }

            $ports = implode("\n", $tmp);
        }

        return $ports;
    }

    private function getLabelsFromString(string $string): MutableCollection
    {
        $labels = new MutableCollection();

        foreach (explode(',', $string) as $label) {
            if (false !== strpos($label, '=')) {
                [$key, $value] = explode('=', $label);

                $labels->set($key, $value);
            }
        }

        return $labels;
    }

    private function getHostFromLabels(MutableCollection $labels): string
    {
        $host = trim(str_replace('Host:', '', $labels->get('traefik.frontend.rule', '')));

        if (strpos($labels->get('com.docker.compose.service'), 'db-') === 0) {
            if (array_key_exists('DOCKER_HOST', $_SERVER)) {
                $host = parse_url($_SERVER['DOCKER_HOST'], PHP_URL_HOST);
            } else {
                $host = 'localhost';
            }
        }

        return $host;
    }
}
