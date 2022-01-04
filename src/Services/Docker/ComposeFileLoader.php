<?php declare(strict_types=1);

namespace Somnambulist\ProjectManager\Services\Docker;

use Somnambulist\ProjectManager\Models\Docker\Components\ComposeNetwork;
use Somnambulist\ProjectManager\Models\Docker\Components\ComposeVolume;
use Somnambulist\ProjectManager\Models\Docker\DockerCompose;
use Somnambulist\ProjectManager\Services\Docker\Factories\ComposeServiceFactory;
use Symfony\Component\Yaml\Yaml;

/**
 * Class ComposeFileConfigurator
 *
 * @package    Somnambulist\ProjectManager\Services\Docker
 * @subpackage Somnambulist\ProjectManager\Services\Docker\ComposeFileConfigurator
 */
class ComposeFileLoader
{
    private ComposeServiceFactory $services;

    public function __construct()
    {
        $this->services = new ComposeServiceFactory();
    }

    public function load(string $file): DockerCompose
    {
        return $this->processConfig(Yaml::parseFile($file));
    }

    public function parse(string $config): DockerCompose
    {
        return $this->processConfig(Yaml::parse($config));
    }

    private function processConfig(array $config): DockerCompose
    {
        $dc = new DockerCompose($config['version']);

        $this->addServices($dc, $config);
        $this->addNetworks($dc, $config);
        $this->addVolumes($dc, $config);

        return $dc;
    }

    private function addServices(DockerCompose $dc, array $data): void
    {
        foreach ($data['services'] as $name => $datum) {
            $dc->services()->register($name, $this->services->from($datum));
        }
    }

    private function addNetworks(DockerCompose $dc, array $data): void
    {
        foreach ($data['networks'] ?? [] as $name => $datum) {
            $net = new ComposeNetwork(
                $datum['name'] ?? null,
                $datum['driver'] ?? null,
                $datum['driver_opts'] ?? [],
                $datum['labels'] ?? [],
                $datum['external'] ?? false
            );

            $dc->networks()->register($name, $net);
        }
    }

    private function addVolumes(DockerCompose $dc, array $data): void
    {
        foreach ($data['volumes'] ?? [] as $name => $datum) {
            $vol = new ComposeVolume(
                $datum['name'] ?? $name,
                $datum['driver'] ?? 'local',
                $datum['driver_opts'] ?? [],
                $datum['labels'] ?? [],
                $datum['external'] ?? false,
            );

            $dc->volumes()->register($name, $vol);
        }
    }
}
