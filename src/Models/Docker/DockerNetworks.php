<?php declare(strict_types=1);

namespace Somnambulist\ProjectManager\Models\Docker;

use Somnambulist\ProjectManager\Models\AbstractElements;
use Somnambulist\ProjectManager\Models\Docker\Components\ComposeNetwork;

/**
 * Class DockerNetworks
 *
 * @package    Somnambulist\ProjectManager\Models\Docker
 * @subpackage Somnambulist\ProjectManager\Models\Docker\DockerNetworks
 *
 * @method null|ComposeNetwork get(string $name)
 */
class DockerNetworks extends AbstractElements
{
    protected string $class = ComposeNetwork::class;

    public function exportForYaml(): array
    {
        return $this
            ->map(fn (ComposeNetwork $s) => $s->exportForYaml())
            ->all()
        ;
    }

    public function getReferenceFromNetworkName(string $name)
    {
        return $this
            ->filter(fn(ComposeNetwork $n)=> $n->name() === $name)
            ->keys()
            ->first()
        ;
    }
}
