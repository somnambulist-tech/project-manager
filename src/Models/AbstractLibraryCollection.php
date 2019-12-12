<?php declare(strict_types=1);

namespace Somnambulist\ProjectManager\Models;

use Countable;use IteratorAggregate;use Somnambulist\ProjectManager\Contracts\InstallableResource;
use Somnambulist\ProjectManager\Contracts\InstallableResourcesCollection;
use Somnambulist\Collection\MutableCollection;

/**
* Class AbstractLibraryCollection
 *
 * @package Somnambulist\ProjectManager\Models
 * @subpackage Somnambulist\ProjectManager\Models\AbstractLibraryCollection
 */
abstract class AbstractLibraryCollection implements InstallableResourcesCollection, Countable, IteratorAggregate
{

    /**
     * @var MutableCollection
     */
    private $items;

    public function __construct()
    {
        $this->items = new MutableCollection();
    }

    public function getIterator()
    {
        return $this->items;
    }

    public function count()
    {
        return $this->items->count();
    }

    public function list(): MutableCollection
    {
        return $this->items;
    }

    public function add(InstallableResource $resource)
    {
        $this->items->set($resource->name(), $resource);

        return $this;
    }

    public function get(string $name): ?InstallableResource
    {
        return $this->items->get($name);
    }
}
