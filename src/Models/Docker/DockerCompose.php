<?php declare(strict_types=1);

namespace Somnambulist\ProjectManager\Models\Docker;

use Somnambulist\ProjectManager\Exceptions\DockerComposeException;
use Somnambulist\ProjectManager\Models\Docker\Components\ComposeService;
use Somnambulist\ProjectManager\Models\Docker\Components\ServiceNetwork;
use Somnambulist\ProjectManager\Models\Docker\Components\ServiceVolume;

/**
 * Class DockerCompose
 *
 * @package    Somnambulist\ProjectManager\Models\Docker
 * @subpackage Somnambulist\ProjectManager\Models\Docker\DockerCompose
 */
class DockerCompose
{

    /**
     * @var string
     */
    private $version;

    /**
     * @var DockerServices
     */
    private $services;

    /**
     * @var DockerNetworks
     */
    private $networks;

    /**
     * @var DockerVolumes
     */
    private $volumes;

    public function __construct(string $version)
    {
        $this->version  = $version;
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
            ->map(function (ComposeService $s) {
                return $s
                    ->volumes()
                    ->filter(function (ServiceVolume $v) {
                        return $v->isVolume();
                    })
                    ->map(function (ServiceVolume $v) {
                        return $v->source();
                    })
                    ->all()
                ;
            })
            ->flatten()
        ;
        $networks = $this->services
            ->map(function (ComposeService $s) {
                return $s
                    ->networks()
                    ->map(function (ServiceNetwork $n) {
                        return $n->name();
                    })
                    ->all()
                ;
            })
            ->flatten()
        ;

        if (!$this->volumes->hasAllOf(...$volumes)) {
            throw DockerComposeException::serviceVolumeNotDefined($volumes);
        }
        if (!$this->networks->hasAllOf(...$networks)) {
            throw DockerComposeException::serviceNetworkNotDefined($networks);
        }
    }
}
