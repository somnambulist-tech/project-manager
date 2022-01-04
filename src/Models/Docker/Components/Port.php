<?php declare(strict_types=1);

namespace Somnambulist\ProjectManager\Models\Docker\Components;

use InvalidArgumentException;

/**
 * Class Port
 *
 * @package    Somnambulist\ProjectManager\Models\Docker\Components
 * @subpackage Somnambulist\ProjectManager\Models\Docker\Components\Port
 */
class Port
{
    public function __construct(
        private int $local,
        private int $docker,
        private string $protocol = 'tcp',
        private string $mode = 'host'
    ) {
        if (!in_array($protocol, ['tcp', 'udp'])) {
            throw new InvalidArgumentException('protocol can be one of: tcp, udp');
        }
        if (!in_array($mode, ['host', 'ingress'])) {
            throw new InvalidArgumentException('mode can be one of: host, ingress');
        }
    }

    public static function from(array $data): Port
    {
        return new Port(
            $data['target'],
            $data['published'],
            $data['protocol'] ?? 'tcp',
            $data['mode'] ?? 'host',
        );
    }

    public function local(): int
    {
        return $this->local;
    }

    public function docker(): int
    {
        return $this->docker;
    }

    public function protocol(): string
    {
        return $this->protocol;
    }

    public function mode(): string
    {
        return $this->mode;
    }

    public function exportForYaml(): string|array
    {
        if ($this->mode === 'ingress') {
            return [
                'target'    => $this->local,
                'published' => $this->docker,
                'protocol'  => $this->protocol,
                'mode'      => $this->mode,
            ];
        }

        return sprintf('%s:%s', $this->local, $this->docker) . ('udp' == $this->protocol ? '/' . $this->protocol : '');
    }
}
