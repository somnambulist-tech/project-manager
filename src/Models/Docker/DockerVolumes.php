<?php declare(strict_types=1);

namespace Somnambulist\ProjectManager\Models\Docker;

use Somnambulist\ProjectManager\Models\AbstractElements;
use Somnambulist\ProjectManager\Models\Docker\Components\ComposeVolume;

/**
 * Class DockerVolumes
 *
 * @package    Somnambulist\ProjectManager\Models\Docker
 * @subpackage Somnambulist\ProjectManager\Models\Docker\DockerVolumes
 *
 * @method null|ComposeVolume get(string $name)
 */
class DockerVolumes extends AbstractElements
{
    protected string $class = ComposeVolume::class;

    public function exportForYaml(): array
    {
        return $this
            ->map(fn (ComposeVolume $s) => $s->exportForYaml())
            ->all()
        ;
    }

    public function hasNamedVolumeOf(string $name): bool
    {
        return $this
            ->filter(fn (ComposeVolume $v) => $v->name() === $name)
            ->count() > 0
        ;
    }
}
