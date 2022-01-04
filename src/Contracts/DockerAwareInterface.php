<?php declare(strict_types=1);

namespace Somnambulist\ProjectManager\Contracts;

use Somnambulist\ProjectManager\Services\DockerManager;

/**
 * Interface DockerAwareInterface
 *
 * @package    Somnambulist\ProjectManager\Contracts
 * @subpackage Somnambulist\ProjectManager\Contracts\DockerAwareInterface
 */
interface DockerAwareInterface
{
    public function bindDockerManager(DockerManager $docker): void;
}
