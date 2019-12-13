<?php declare(strict_types=1);

namespace Somnambulist\ProjectManager\Commands\Services;

use Somnambulist\ProjectManager\Commands\AbstractCommand;
use Somnambulist\Collection\MutableCollection;
use Somnambulist\ProjectManager\Commands\Behaviours\DockerAwareCommand;
use Somnambulist\ProjectManager\Commands\Behaviours\GetCurrentActiveProject;
use Somnambulist\ProjectManager\Contracts\DockerAwareInterface;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use function array_key_exists;
use function explode;
use function getenv;
use function implode;
use function parse_url;
use function sprintf;
use function str_replace;
use function strlen;
use function strpos;
use const PHP_URL_HOST;

/**
 * Class StatusCommand
 *
 * @package Somnambulist\ProjectManager\Commands\Services
 * @subpackage Somnambulist\ProjectManager\Commands\Services\StatusCommand
 */
class StatusCommand extends AbstractCommand implements DockerAwareInterface
{

    use GetCurrentActiveProject;
    use DockerAwareCommand;

    protected function configure()
    {
        $this
            ->setName('services:status')
            ->setDescription('Displays information about the currently running services')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $project = $this->getActiveProject();
        $data    = $this->docker->status($prefix = getenv('COMPOSE_PROJECT_NAME'));

        $data->sortUsing(function ($r1, $r2) {
            if ($r1['name'] == $r2['name']) {
                return 0;
            }

            return $r1['name'] > $r2['name'];
        });

        $output->writeln('');

        $table = new Table($output);
        $table
            ->setHeaderTitle(sprintf('Services Status (project: <fg=yellow;bg=white>%s</>)', $project->name()))
            ->setHeaders(['Running Container Name', 'Status', 'Host', 'Port', 'Mounts'])
        ;

        $data->each(function (array $row) use ($table, $prefix) {
            $mounts = $this->getMountsFromString($row['mounts']);
            $ports  = $this->getPortsFromString($row['ports']);
            $labels = $this->getLabelsFromString($row['labels']);
            $host   = $this->getHostFromLabels($labels);

            $table->addRow([
                $row['name'],
                explode(' ', $row['status'])[0],
                $host,
                $ports,
                $mounts,
            ]);
        });

        $table->render();

        $output->writeln('');

        return 0;
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

    private function getLabelsFromString(string $string): MutableCollection
    {
        $labels = new MutableCollection();

        foreach (explode(',', $string) as $label) {
            [$key, $value] = explode('=', $label);
            $labels->set($key, $value);
        }

        return $labels;
    }
}
