<?php declare(strict_types=1);

namespace Somnambulist\ProjectManager\Models;

use ArrayAccess;
use ArrayIterator;
use Countable;
use IteratorAggregate;
use Traversable;
use function array_filter;
use function array_key_exists;
use function array_map;
use function array_search;
use function array_values;
use function preg_match;
use function reset;
use const ARRAY_FILTER_USE_BOTH;

/**
 * Class Options
 *
 * @package    Somnambulist\ProjectManager\Models
 * @subpackage Somnambulist\ProjectManager\Models\Options
 */
class Options implements ArrayAccess, Countable, IteratorAggregate
{
    public function __construct(private array $items = [])
    {
    }

    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->items);
    }

    public function offsetExists($offset): bool
    {
        return array_key_exists($offset, $this->items);
    }

    public function offsetGet($offset): mixed
    {
        return $this->items[$offset] ?? null;
    }

    public function offsetSet($offset, $value): void
    {
        if (null === $offset) {
            $this->items[] = $value;
        } else {
            $this->items[$offset] = $value;
        }
    }

    public function offsetUnset($offset): void
    {
        unset($this->items[$offset]);
    }

    public function count(): int
    {
        return count($this->items);
    }

    public function all(): array
    {
        return $this->items;
    }

    public function first(): mixed
    {
        return reset($this->items);
    }

    public function last(): mixed
    {
        return end($this->items);
    }

    public function find(callable $func): mixed
    {
        return array_filter($this->items, $func, ARRAY_FILTER_USE_BOTH)[0] ?? null;
    }

    public function filter(callable $func): Options
    {
        return new Options(array_filter($this->items, $func, ARRAY_FILTER_USE_BOTH));
    }

    public function flatten(): array
    {
        $return = [];

        foreach ($this->items as $key => $values) {
            if (is_array($values)) {
                $return = array_merge($return, $values);
            } elseif ($values instanceof Options) {
                $return = array_merge($return, $values->flatten());
            } else {
                $return[$key] = $values;
            }
        }

        return $return;
    }

    public function each(callable $func): self
    {
        foreach ($this->items as $key => $value) {
            if (false === $func($value, $key)) {
                break;
            }
        }

        return $this;
    }

    public function keys(): Options
    {
        return new Options(array_keys($this->items));
    }

    public function values(): Options
    {
        return new Options(array_values($this->items));
    }

    public function map(callable $func): Options
    {
        $keys  = array_keys($this->items);
        $items = array_map($func, $this->items, $keys);

        return new Options(array_combine($keys, $items));
    }

    public function has(string $option): bool
    {
        return $this->offsetExists($option);
    }

    public function hasAllOf(string ...$option): bool
    {
        $ret = true;

        foreach ($option as $opt) {
            $ret = $ret && $this->has($opt);
        }

        return $ret;
    }

    public function hasAnyOf(string ...$option): bool
    {
        foreach ($option as $opt) {
            if ($this->has($opt)) {
                return true;
            }
        }

        return false;
    }

    public function matching(string $rule): Options
    {
        $ret = new Options();

        foreach ($this->items as $key => $value) {
            if (false !== preg_match($rule, $key)) {
                $ret->set($key, $value);
            }
        }

        return $ret;
    }

    public function get(int|string $option, mixed $default = null)
    {
        if (null === $val = $this->offsetGet($option)) {
            return $default;
        }

        return $val;
    }

    public function add(mixed $value): self
    {
        $this->offsetSet(null, $value);

        return $this;
    }

    public function set(int|string $option, mixed $value): self
    {
        $this->offsetSet($option, $value);

        return $this;
    }

    public function remove(mixed $value): self
    {
        if (false !== $key = array_search($value, $this->items, true)) {
            $this->unset($key);
        }

        return $this;
    }

    public function unset(int|string $option): self
    {
        $this->offsetUnset($option);

        return $this;
    }
}
