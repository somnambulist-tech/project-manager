<?php declare(strict_types=1);

namespace Somnambulist\ProjectManager\Models;

use Countable;
use IteratorAggregate;
use Somnambulist\Collection\MutableCollection;

/**
 * Class Projects
 *
 * @package Somnambulist\ProjectManager\Models
 * @subpackage Somnambulist\ProjectManager\Models\Projects
 */
final class Projects implements Countable, IteratorAggregate
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
