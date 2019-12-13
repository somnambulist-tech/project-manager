<?php declare(strict_types=1);

namespace Somnambulist\ProjectManager\Commands\Behaviours;

use Somnambulist\ProjectManager\Services\DockerManager;

/**
 * Trait DockerAwareCommand
 *
 * @package    Somnambulist\ProjectManager\Commands\Behaviours
 * @subpackage Somnambulist\ProjectManager\Commands\Behaviours\DockerAwareCommand
 */
trait DockerAwareCommand
{

    /**
     * @var DockerManager
     */
    protected $docker;

    public function bindDockerManager(DockerManager $docker): void
    {
        $this->docker = $docker;
    }
}
