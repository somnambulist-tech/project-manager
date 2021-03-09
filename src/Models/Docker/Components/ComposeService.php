<?php declare(strict_types=1);

namespace Somnambulist\ProjectManager\Models\Docker\Components;

use IlluminateAgnostic\Str\Support\Str;
use InvalidArgumentException;
use Somnambulist\ProjectManager\Models\Options;
use function is_null;
use function is_numeric;

/**
 * Class ComposeService
 *
 * @package    Somnambulist\ProjectManager\Models\Docker\Components
 * @subpackage Somnambulist\ProjectManager\Models\Docker\Components\ComposeService
 */
class ComposeService
{

    /**
     * @var string|null
     */
    private $name;

    /**
     * @var string|null
     */
    private $image;

    /**
     * @var Build|null
     */
    private $build;

    /**
     * @var string
     */
    private $restart;

    /**
     * @var Options
     */
    private $dependsOn;

    /**
     * @var Options
     */
    private $environment;

    /**
     * @var Options
     */
    private $command;

    /**
     * @var Options
     */
    private $ports;

    /**
     * @var Options
     */
    private $volumes;

    /**
     * @var Options
     */
    private $networks;

    /**
     * @var Options
     */
    private $labels;

    /**
     * @var HealthCheck|null
     */
    private $healthcheck;

    /**
     * @var Logging|null
     */
    private $logging;

    public function __construct(
        ?string $name,
        ?string $image = null,
        ?Build $build = null,
        string $restart = 'no',
        array $dependsOn = [],
        array $environment = [],
        array $command = [],
        array $ports = [],
        array $volumes = [],
        array $networks = [],
        array $labels = [],
        ?HealthCheck $healthcheck = null,
        ?Logging $logging = null
    ) {
        if (is_numeric($image) && is_null($build)) {
            throw new InvalidArgumentException('The image or build must be specified');
        }
        if (!in_array($restart, $r = ['no', 'always', 'on-failure', 'unless-stopped'])) {
            throw new InvalidArgumentException(sprintf('Restart must be one of: %s', implode(', ', $r)));
        }

        $this->name        = $name;
        $this->image       = $image;
        $this->build       = $build;
        $this->restart     = $restart;
        $this->dependsOn   = new Options($dependsOn);
        $this->environment = new Options($environment);
        $this->command     = new Options($command);
        $this->ports       = new Options($ports);
        $this->volumes     = new Options($volumes);
        $this->networks    = new Options($networks);
        $this->labels      = new Options($labels);
        $this->healthcheck = $healthcheck;
        $this->logging     = $logging;
    }

    public function name(): ?string
    {
        return $this->name;
    }

    public function image(): ?string
    {
        return $this->image;
    }

    public function build(): ?Build
    {
        return $this->build;
    }

    public function restart(): string
    {
        return $this->restart;
    }

    public function dependsOn(): Options
    {
        return $this->dependsOn;
    }

    public function environment(): Options
    {
        return $this->environment;
    }

    public function command(): Options
    {
        return $this->command;
    }

    public function ports(): Options
    {
        return $this->ports;
    }

    public function volumes(): Options
    {
        return $this->volumes;
    }

    public function networks(): Options
    {
        return $this->networks;
    }

    public function labels(): Options
    {
        return $this->labels;
    }

    public function healthcheck(): ?HealthCheck
    {
        return $this->healthcheck;
    }

    public function logging(): ?Logging
    {
        return $this->logging;
    }

    public function exportForYaml(): array
    {
        $ret = [];

        if ($this->name) {
            $ret['container_name'] = $this->name;
        }
        if ($this->image) {
            $ret['image'] = $this->image;
        }
        if ($this->build) {
            $ret['build'] = $this->build->exportForYaml();
        }
        if ($this->restart !== 'no') {
            $ret['restart'] = $this->restart;
        }

        foreach (['dependsOn', 'environment', 'command', 'labels'] as $prop) {
            if ($this->{$prop}->count()) {
                $ret[Str::snake($prop)] = $this->{$prop}->all();
            }
        }

        if ($this->volumes->count()) {
            $ret['volumes'] = $this->volumes
                ->map(function (ServiceVolume $v) {
                    return $v->exportForYaml();
                })
                ->all()
            ;
        }
        if ($this->ports->count()) {
            $ret['ports'] = $this->ports
                ->map(function (Port $v) {
                    return $v->exportForYaml();
                })
                ->all()
            ;
        }
        if ($this->networks->count()) {
            $ret['networks'] = $this->networks
                ->map(function (ServiceNetwork $value, $key) {
                    return $value->exportForYaml();
                })
                ->all()
            ;
        }

        if ($this->healthcheck) {
            $ret['healthcheck'] = $this->healthcheck->exportForYaml();
        }
        if ($this->logging) {
            $ret['logging'] = $this->logging->exportForYaml();
        }

        return $ret;
    }
}
