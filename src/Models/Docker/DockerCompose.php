<?php declare(strict_types=1);

namespace Somnambulist\ProjectManager\Models\Docker;

use Somnambulist\ProjectManager\Exceptions\DockerComposeException;
use Somnambulist\ProjectManager\Models\Docker\Components\ComposeService;
use Somnambulist\ProjectManager\Models\Docker\Components\Port;
use Somnambulist\ProjectManager\Models\Docker\Components\ServiceNetwork;
use Somnambulist\ProjectManager\Models\Docker\Components\ServiceVolume;
use function array_key_exists;

/**
 * Class DockerCompose
 *
 * @package    Somnambulist\ProjectManager\Models\Docker
 * @subpackage Somnambulist\ProjectManager\Models\Docker\DockerCompose
 */
class DockerCompose
{
    private DockerServices $services;
    private DockerNetworks $networks;
    private DockerVolumes $volumes;

    public function __construct(private string $version)
    {
        $this->services = new DockerServices();
        $this->networks = new DockerNetworks();
        $this->volumes  = new DockerVolumes();
    }

    public function version(): string
    {
        return $this->version;
    }

    public function services(): DockerServices
    {
        return $this->services;
    }

    public function networks(): DockerNetworks
    {
        return $this->networks;
    }

    public function volumes(): DockerVolumes
    {
        return $this->volumes;
    }

    public function exportForYaml(): array
    {
        $ret = ['version' => $this->version];

        if ($this->services->count()) {
            $ret['services'] = $this->services->exportForYaml();
        }
        if ($this->networks->count()) {
            $ret['networks'] = $this->networks->exportForYaml();
        }
        if ($this->volumes->count()) {
            $ret['volumes'] = $this->volumes->exportForYaml();
        }

        return $ret;
    }

    public function validate()
    {
        $volumes = $this->services
            ->map(
                fn(ComposeService $s) => $s->volumes()->filter(fn(ServiceVolume $v) => $v->isVolume())->map(fn(ServiceVolume $v) => $v->source())->all()
            )
            ->flatten()
        ;
        $networks = $this->services
            ->map(fn(ComposeService $s) => $s->networks()->map(fn(ServiceNetwork $n) => $n->name())->all())
            ->flatten()
        ;

        if (!$this->volumes->hasAllOf(...$volumes)) {
            throw DockerComposeException::serviceVolumeNotDefined($volumes);
        }
        if (!$this->networks->hasAllOf(...$networks)) {
            throw DockerComposeException::serviceNetworkNotDefined($networks);
        }

        $ports = [];
        $this->services->each(function (ComposeService $s, $name) use (&$ports) {
            $s->ports()->each(function (Port $p) use ($s, $name, &$ports) {
                if (!array_key_exists($p->local(), $ports)) {
                    $ports[$p->local()] = $name;
                } else {
                    throw DockerComposeException::portAlreadyAssigned($p->local(), $ports[$p->local()]);
                }
            });
        });
    }
}
