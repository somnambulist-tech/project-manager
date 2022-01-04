<?php declare(strict_types=1);

namespace Somnambulist\ProjectManager\Models\Docker\Components;

use Somnambulist\ProjectManager\Models\Options;

/**
 * Class Logging
 *
 * @package    Somnambulist\ProjectManager\Models\Docker\Components
 * @subpackage Somnambulist\ProjectManager\Models\Docker\Components\Logging
 */
class Logging
{
    private Options $options;

    public function __construct(private ?string $driver = null, array $options = [])
    {
        $this->options = new Options($options);
    }

    public static function from(array $data): ?Logging
    {
        if (empty($data)) {
            return null;
        }

        return new Logging(
            $data['driver'] ?? null,
            $data['options'] ?? [],
        );
    }

    public function driver(): ?string
    {
        return $this->driver;
    }

    public function options(): Options
    {
        return $this->options;
    }

    public function exportForYaml(): array
    {
        $ret = [];

        if ($this->driver) {
            $ret['driver'] = $this->driver;
        }
        if ($this->options) {
            $ret['options'] = $this->options->all();
        }

        return $ret;
    }
}
