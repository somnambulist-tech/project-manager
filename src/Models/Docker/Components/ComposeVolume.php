<?php declare(strict_types=1);

namespace Somnambulist\ProjectManager\Models\Docker\Components;

use Somnambulist\ProjectManager\Models\Options;

/**
 * Class ComposeVolume
 *
 * @package    Somnambulist\ProjectManager\Models\Docker\Components
 * @subpackage Somnambulist\ProjectManager\Models\Docker\Components\ComposeVolume
 */
class ComposeVolume
{

    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $driver;

    /**
     * @var Options
     */
    private $options;

    /**
     * @var Options
     */
    private $labels;

    /**
     * @var bool
     */
    private $external;

    public function __construct(string $name, string $driver = null, array $options = [], array $labels = [], bool $external = false)
    {
        $this->name     = $name;
        $this->driver   = $driver;
        $this->options  = new Options($options);
        $this->labels   = new Options($labels);
        $this->external = $external;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function driver(): ?string
    {
        return $this->driver;
    }

    public function options(): Options
    {
        return $this->options;
    }

    public function labels(): Options
    {
        return $this->labels;
    }

    public function isExternal(): bool
    {
        return $this->external;
    }

    public function exportForYaml(): array
    {
        $ret = ['name' => $this->name];

        if ($this->driver !== 'local') {
            $ret['driver'] = $this->driver;
        }
        if ($this->external) {
            $ret['external'] = true;
        }
        if ($this->options->count()) {
            $ret['driver_opts'] = $this->options->all();
        }
        if ($this->labels->count()) {
            $ret['labels'] = $this->labels->all();
        }

        return $ret;
    }
}
