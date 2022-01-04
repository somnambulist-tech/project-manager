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
    private Options $options;
    private Options $labels;

    public function __construct(
        private ?string $name,
        private ?string $driver = null,
        array $options = [],
        array $labels = [],
        private bool $external = false)
    {
        $this->options  = new Options($options);
        $this->labels   = new Options($labels);
    }

    public function type(): string
    {
        return 'volume';
    }

    public function name(): ?string
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

    public function exportForYaml(): ?array
    {
        if (!$this->name && !$this->labels->count() && !$this->options->count() && !$this->driver) {
            return null;
        }

        $ret = ['name' => $this->name];

        if ($this->driver && $this->driver !== 'local') {
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
