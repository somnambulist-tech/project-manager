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

    protected $class = ComposeVolume::class;

    public function exportForYaml(): array
    {
        return $this
            ->map(function (ComposeVolume $s) {
                return $s->exportForYaml();
            })
            ->all()
        ;
    }
}
