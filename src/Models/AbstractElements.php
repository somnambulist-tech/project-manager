<?php declare(strict_types=1);

namespace Somnambulist\ProjectManager\Models;

use InvalidArgumentException;
use Somnambulist\ProjectManager\Exceptions\DockerComposeException;

/**
 * Class AbstractElements
 *
 * @package    Somnambulist\ProjectManager\Models
 * @subpackage Somnambulist\ProjectManager\Models\AbstractElements
 */
class AbstractElements extends Options
{
    protected string $class;

    public function __construct(array $values = [])
    {
        parent::__construct([]);

        foreach ($values as $key => $value) {
            $this->set($key, $value);
        }
    }

    public function offsetSet($offset, $value): void
    {
        if (!$value instanceof $this->class) {
            throw new InvalidArgumentException(sprintf('Only instances of %s can be added', $this->class));
        }

        parent::offsetSet($offset, $value);
    }

    /**
     * @throws DockerComposeException
     */
    public function register(string $name, $value): AbstractElements
    {
        if ($this->has($name)) {
            throw DockerComposeException::elementAlreadyDefined($value->type(), $name);
        }

        return $this->set($name, $value);
    }
}
