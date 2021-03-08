<?php declare(strict_types=1);

namespace Somnambulist\ProjectManager\Models;

use InvalidArgumentException;

/**
 * Class AbstractElements
 *
 * @package    Somnambulist\ProjectManager\Models
 * @subpackage Somnambulist\ProjectManager\Models\AbstractElements
 */
class AbstractElements extends Options
{

    /**
     * @var string
     */
    protected $class;

    public function __construct(array $values = [])
    {
        parent::__construct([]);

        foreach ($values as $key => $value) {
            $this->set($key, $value);
        }
    }

    public function offsetSet($offset, $value)
    {
        if (!$value instanceof $this->class) {
            throw new InvalidArgumentException(sprintf('Only instances of %s can be added', $this->class));
        }

        parent::offsetSet($offset, $value);
    }

    public function register(string $name, $value): AbstractElements
    {
        return $this->set($name, $value);
    }

    public function remove(string $name): AbstractElements
    {
        return $this->unset($name);
    }
}
