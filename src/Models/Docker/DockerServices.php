<?php declare(strict_types=1);

namespace Somnambulist\ProjectManager\Models\Docker;

use Somnambulist\ProjectManager\Models\AbstractElements;
use Somnambulist\ProjectManager\Models\Docker\Components\ComposeService;

/**
 * Class DockerServices
 *
 * @package    Somnambulist\ProjectManager\Models\Docker
 * @subpackage Somnambulist\ProjectManager\Models\Docker\DockerServices
 *
 * @method null|ComposeService get(string $name)
 */
class DockerServices extends AbstractElements
{
    protected string $class = ComposeService::class;

    public function exportForYaml(): array
    {
        return $this
            ->map(fn (ComposeService $s) => $s->exportForYaml())
            ->all()
        ;
    }
}
