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

    /**
     * @var int
     */
    private $target;

    /**
     * @var int
     */
    private $published;

    /**
     * @var string
     */
    private $protocol;

    /**
     * @var string
     */
    private $mode;

    public function __construct(int $local, int $docker, string $protocol = 'tcp', string $mode = 'host')
    {
        if (!in_array($protocol, ['tcp', 'udp'])) {
            throw new InvalidArgumentException('protocol can be one of: tcp, udp');
        }
        if (!in_array($mode, ['host', 'ingress'])) {
            throw new InvalidArgumentException('mode can be one of: host, ingress');
        }

        $this->target    = $local;
        $this->published = $docker;
        $this->protocol  = $protocol;
        $this->mode      = $mode;
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

    public function target(): int
    {
        return $this->target;
    }

    public function published(): int
    {
        return $this->published;
    }

    public function protocol(): string
    {
        return $this->protocol;
    }

    public function mode(): string
    {
        return $this->mode;
    }

    /**
     * @return array|string
     */
    public function exportForYaml()
    {
        if ($this->mode === 'ingress') {
            return [
                'target'    => $this->target,
                'published' => $this->published,
                'protocol'  => $this->protocol,
                'mode'      => $this->mode,
            ];
        }

        return sprintf('%s:%s', $this->target, $this->published) . ('udp' == $this->protocol ? '/' . $this->protocol : '');
    }
}
