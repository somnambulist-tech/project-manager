<?php declare(strict_types=1);

namespace Somnambulist\ProjectManager\Exceptions;

use InvalidArgumentException;

/**
 * Class ResourceNotFound
 *
 * @package    Somnambulist\ProjectManager\Exceptions
 * @subpackage Somnambulist\ProjectManager\Exceptions\ResourceNotFound
 */
class ResourceNotFound extends InvalidArgumentException
{

    public static function raise(string $name)
    {
        return new static(sprintf('Library <info>%s</info> was not found, does it exist?', $name));
    }
}
