<?php declare(strict_types=1);

namespace Somnambulist\ProjectManager\Models\Docker\Components;

use InvalidArgumentException;
use Somnambulist\ProjectManager\Models\Options;
use function in_array;

/**
 * Class ComposeNetwork
 *
 * @package    Somnambulist\ProjectManager\Models\Docker\Components
 * @subpackage Somnambulist\ProjectManager\Models\Docker\Components\ComposeNetwork
 */
class ComposeNetwork
{

    /**
     * @var string|null
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

    public function __construct(?string $name, string $driver = 'bridge', array $options = [], array $labels = [], bool $external = false)
    {
        if (!in_array($driver, $a = ['bridge', 'overlay', 'host', 'none'])) {
            throw new InvalidArgumentException(sprintf('Network driver must be one of: %s', implode(', ', $a)));
        }

        $this->name     = $name;
        $this->driver   = $driver;
        $this->options  = new Options($options);
        $this->labels   = new Options($labels);
        $this->external = $external;
    }

    public function type(): string
    {
        return 'network';
    }

    public function name(): string
    {
        return $this->name;
    }

    public function driver(): string
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
        if (!$this->name && !$this->options->count() && !$this->labels->count()) {
            return null;
        }

        $ret = [
            'name'   => $this->name,
            'driver' => $this->driver,
        ];

        if ($this->options->count()) {
            $ret['driver_opts'] = $this->options->all();
        }
        if ($this->labels->count()) {
            $ret['labels'] = $this->labels->all();
        }
        if ($this->external) {
            $ret['external'] = true;
        }

        return $ret;
    }
}
