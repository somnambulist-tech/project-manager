<?php declare(strict_types=1);

namespace Somnambulist\ProjectManager\Contracts;

use Somnambulist\Components\Collection\MutableCollection;

/**
 * Interface InstallableResourcesCollection
 *
 * @package Somnambulist\ProjectManager\Contracts
 * @subpackage Somnambulist\ProjectManager\Contracts\InstallableResourcesCollection
 */
interface InstallableResourcesCollection
{
    public function list(): MutableCollection;

    public function add(InstallableResource $resource);

    public function get(string $name): ?InstallableResource;
}
