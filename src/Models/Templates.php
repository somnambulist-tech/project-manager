<?php declare(strict_types=1);

namespace Somnambulist\ProjectManager\Models;

use Countable;
use IteratorAggregate;
use Somnambulist\Collection\MutableCollection;

/**
* Class Templates
 *
 * @package Somnambulist\ProjectManager\Models
 * @subpackage Somnambulist\ProjectManager\Models\Templates
 */
final class Templates implements Countable, IteratorAggregate
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

    public function for(string $type): array
    {
        return $this
            ->items
            ->filter(function (Template $t) use ($type) {
                return $t->type() === $type;
            })
            ->extract('name')
            ->toArray()
        ;
    }

    public function list(): MutableCollection
    {
        return $this->items;
    }

    public function add(Template $resource): self
    {
        $this->items->set($resource->name(), $resource);

        return $this;
    }

    public function get(string $name): ?Template
    {
        return $this->items->get($name);
    }
}
