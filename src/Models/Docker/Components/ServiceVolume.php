<?php declare(strict_types=1);

namespace Somnambulist\ProjectManager\Models\Docker\Components;

use InvalidArgumentException;
use Somnambulist\ProjectManager\Models\Options;

/**
 * Class ServiceVolume
 *
 * @package    Somnambulist\ProjectManager\Models\Docker\Components
 * @subpackage Somnambulist\ProjectManager\Models\Docker\Components\ServiceVolume
 */
class ServiceVolume
{

    /**
     * @var string
     */
    private $type;

    /**
     * @var string|null
     */
    private $source;

    /**
     * @var string
     */
    private $target;

    /**
     * @var bool
     */
    private $readOnly;

    /**
     * @var Options
     */
    private $bind;

    /**
     * @var Options
     */
    private $volume;

    /**
     * @var Options
     */
    private $tmpfs;

    public function __construct(string $type, ?string $source, string $target, bool $readOnly = false, array $bind = [], array $volume = [], array $tmpfs = [])
    {
        if (!in_array($type, $t = ['volume', 'bind', 'tmpfs', 'npipe'])) {
            throw new InvalidArgumentException(sprintf('Type must be one of: %s', implode(', ', $t)));
        }

        $this->type     = $type;
        $this->source   = $source;
        $this->target   = $target;
        $this->readOnly = $readOnly;
        $this->bind     = new Options($bind);
        $this->volume   = new Options($volume);
        $this->tmpfs    = new Options($tmpfs);
    }

    public static function from(array $data): ServiceVolume
    {
        return new ServiceVolume(
            $data['type'],
            $data['source'],
            $data['target'],
            $data['read_only'],
            $data['bind'] ?? [],
            $data['volume'] ?? [],
            $data['tmpfs'] ?? [],
        );
    }

    public function isVolume(): bool
    {
        return $this->type === 'volume';
    }

    public function isBind(): bool
    {
        return $this->type === 'bind';
    }

    public function type(): string
    {
        return $this->type;
    }

    public function source(): ?string
    {
        return $this->source;
    }

    public function target(): string
    {
        return $this->target;
    }

    public function isReadOnly(): bool
    {
        return $this->readOnly;
    }

    public function bind(): Options
    {
        return $this->bind;
    }

    public function volume(): Options
    {
        return $this->volume;
    }

    public function tmpfs(): Options
    {
        return $this->tmpfs;
    }

    /**
     * @return array|string
     */
    public function exportForYaml()
    {
        if (!$this->bind->count() && !$this->volume->count() && !$this->tmpfs->count()) {
            return sprintf('%s:%s', $this->source, $this->target) . ($this->readOnly ? ':ro' : '');
        }

        $ret = [
            'type'   => $this->type,
            'source' => $this->source,
            'target' => $this->target,
        ];

        if ($this->readOnly) {
            $ret['read_only'] = true;
        }
        if ($this->bind->count()) {
            $ret['bind'] = $this->bind->all();
        }
        if ($this->volume->count()) {
            $ret['volume'] = $this->volume->all();
        }
        if ($this->tmpfs->count()) {
            $ret['tmpfs'] = $this->tmpfs->all();
        }

        return $ret;
    }
}
