<?php declare(strict_types=1);

namespace Somnambulist\ProjectManager\Models;

use Countable;
use IteratorAggregate;
use Somnambulist\Components\Collection\MutableCollection;
use Traversable;

/**
 * Class Projects
 *
 * @package Somnambulist\ProjectManager\Models
 * @subpackage Somnambulist\ProjectManager\Models\Projects
 */
final class Projects implements Countable, IteratorAggregate
{
    private MutableCollection $items;

    public function __construct()
    {
        $this->items = new MutableCollection();
    }

    public function getIterator(): Traversable
    {
        return $this->items;
    }

    public function count(): int
    {
        return $this->items->count();
    }

    public function names(): array
    {
        return $this->items->extract('name')->toArray();
    }

    public function list(): MutableCollection
    {
        return $this->items;
    }

    public function add(Project $resource): self
    {
        $this->items->set($resource->name(), $resource);

        return $this;
    }

    public function get(string $name): ?Project
    {
        return $this->items->get($name);
    }

    public function active(): ?Project
    {
        return $this->get($_SERVER['SOMNAMBULIST_ACTIVE_PROJECT'] ?? '');
    }
}
