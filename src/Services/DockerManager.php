<?php declare(strict_types=1);

namespace Somnambulist\ProjectManager\Services;

use Exception;
use RuntimeException;
use Somnambulist\Collection\MutableCollection;
use Somnambulist\ProjectManager\Models\Service;
use Somnambulist\ProjectManager\Services\Console\ConsoleHelper;
use Symfony\Component\Dotenv\Dotenv;
use function array_combine;
use function array_filter;
use function count;
use function exec;
use function explode;
use function file_get_contents;
use function implode;
use function sprintf;
use function trim;

/**
 * Class DockerManager
 *
 * @link https://docs.docker.com/engine/reference/commandline/ps/#formatting
 * @link https://github.com/dave-redfern/somnambulist-sync-it/blob/master/src/Services/DockerContainerResolver.php
 *
 * Available template placeholders:
 *
 * Placeholder  Description
 * .ID          Container ID
 * .Image       Image ID
 * .Command     Quoted command
 * .CreatedAt   Time when the container was created.
 * .RunningFor  Elapsed time since the container was started.
 * .Ports       Exposed ports.
 * .Status      Container status.
 * .Size        Container disk size.
 * .Names       Container names.
 * .Labels      All labels assigned to the container.
 * .Label       Value of a specific label for this container. For example '{{.Label "com.docker.swarm.cpu"}}'
 * .Mounts      Names of the volumes mounted in this container.
 * .Networks    Names of the networks attached to this container.
 *
 * @package Somnambulist\ProjectManager\Services
 * @subpackage Somnambulist\ProjectManager\Services\DockerManager
 */
class DockerManager
{

    /**
     * @var ConsoleHelper
     */
    private $helper;

    /**
     * An array of ENV names that must not be passed through to other commands
     *
     * @var array
     */
    private $toRemove = [
        'APP_ENV'              => false,
        'COMPOSE_PROJECT_NAME' => false,
        'SYMFONY_DOTENV_VARS'  => false,
    ];

    public function bindConsoleHelper(ConsoleHelper $helper): void
    {
        $this->helper = $helper;
    }

    public function resolve(Service $service): void
    {
        if (!$service->isInstalled()) {
            return;
        }

        $env = (new Dotenv())->parse(file_get_contents($service->envFile()));
        $name = implode('_', array_filter([$env['COMPOSE_PROJECT_NAME'] ?? '', $service->appContainer()]));

        try {
            $command = sprintf('docker ps --no-trunc --format="{{.ID}}" --filter=name="%s"', $name);

            $success    = null;
            $containers = [];

            /*
             * exec is used here because SF\Process was producing no output, but running OK.
             */
            exec($command, $containers, $success);

            if (0 !== $success) {
                throw new RuntimeException(sprintf('Unable to query docker, exit code was "%s"', $success));
            }
            if (count($containers) == 0) {
                throw new RuntimeException(sprintf('No containers found matching name "%s"', $service->appContainer()));
            }
            if (count($containers) > 1) {
                throw new RuntimeException(
                    sprintf('Multiple matches for "%s"; use a more specific name ("%s")', $service->appContainer(), implode('", "', $containers))
                );
            }

            $container = trim($containers[0]);

            $service->start($container);
        } catch (Exception $e) {
            $service->stop();
        }
    }

    public function status(string $filter): MutableCollection
    {
        $format = '{{.ID}}||{{.Image}}||{{.Names}}||{{.RunningFor}}||{{.Ports}}||{{.Status}}||{{.Size}}||{{.Mounts}}||{{.Labels}}';

        $command = 'docker ps --no-trunc';
        $command .= sprintf(' --format="%s"', $format);
        $command .= sprintf(' --filter=name="%s"', $filter);

        $success    = null;
        $containers = [];

        /*
         * exec is used here because SF\Process was producing no output, but running OK.
         */
        exec($command, $containers, $success);

        if (0 !== $success) {
            throw new RuntimeException(sprintf('Unable to query docker, exit code was "%s"', $success));
        }

        $status = new MutableCollection();

        foreach ($containers as $container) {
            $status->add(
                array_combine(
                    ['id', 'image', 'name', 'uptime', 'ports', 'status', 'size', 'mounts', 'labels'],
                    explode('||', $container)
                )
            );
        }

        return $status;
    }

    public function build(Service $service): bool
    {
        $this->stop($service);

        return $this->runCommand($service, 'docker-compose build --no-cache');
    }

    public function refresh(Service $service): bool
    {
        $this->stop($service);

        return $this->runCommand($service, 'docker-compose build --no-cache --pull');
    }

    public function reset(): bool
    {
        return $this->helper->execute('docker system prune --all --volumes');
    }

    public function start(Service $service): bool
    {
        $this->resolve($service);

        if ($service->isRunning()) {
            return true;
        }

        return $this->runCommand($service, 'docker-compose up -d');
    }

    public function stop(Service $service): bool
    {
        return $this->runCommand($service, 'docker-compose down');
    }

    private function runCommand(Service $service, string $command): bool
    {
        try {
            if ($service->isInstalled()) {
                if (true === $res = $this->helper->execute($command, $service->installPath(), $this->toRemove)) {
                    $this->resolve($service);
                }

                return $res;
            }
        } catch (Exception $e) {
        }

        return false;
    }
}
