<?php declare(strict_types=1);

namespace Somnambulist\ProjectManager\Models\Docker\Components;

use Somnambulist\ProjectManager\Models\Options;

/**
 * Class Build
 *
 * @package    Somnambulist\ProjectManager\Models\Docker\Components
 * @subpackage Somnambulist\ProjectManager\Models\Docker\Components\Build
 */
class Build
{
    private Options $args;
    private Options $cacheFrom;
    private Options $labels;

    public function __construct(
        private string $context,
        private string $dockerfile,
        array $args = [],
        array $cacheFrom = [],
        array $labels = [],
        private ?string $network = null,
        private ?string $shmSize = null,
        private ?string $target = null
    ) {
        $this->args       = new Options($args);
        $this->cacheFrom  = new Options($cacheFrom);
        $this->labels     = new Options($labels);
    }

    public static function from(array $data): ?Build
    {
        if (empty($data)) {
            return null;
        }

        return new Build(
            $data['context'],
            $data['dockerfile'],
            $data['args'] ?? [],
            $data['cache_from'] ?? [],
            $data['labels'] ?? [],
            $data['network'] ?? null,
            $data['shm_size'] ?? null,
            $data['target'] ?? null,
        );
    }

    public function context(): string
    {
        return $this->context;
    }

    public function dockerfile(): string
    {
        return $this->dockerfile;
    }

    public function args(): Options
    {
        return $this->args;
    }

    public function cacheFrom(): Options
    {
        return $this->cacheFrom;
    }

    public function labels(): Options
    {
        return $this->labels;
    }

    public function network(): ?string
    {
        return $this->network;
    }

    public function shmSize(): ?string
    {
        return $this->shmSize;
    }

    public function target(): ?string
    {
        return $this->target;
    }

    public function exportForYaml(): array
    {
        $ret = [
            'context'    => $this->context,
            'dockerfile' => $this->dockerfile,
        ];

        if ($this->args->count()) {
            $ret['args'] = $this->args->all();
        }
        if ($this->cacheFrom->count()) {
            $ret['cache_from'] = $this->cacheFrom->all();
        }
        if ($this->labels->count()) {
            $ret['labels'] = $this->cacheFrom->all();
        }
        if ($this->network) {
            $ret['network'] = $this->network;
        }
        if ($this->shmSize) {
            $ret['shm_size'] = $this->shmSize;
        }
        if ($this->target) {
            $ret['target'] = $this->target;
        }

        return $ret;
    }
}
