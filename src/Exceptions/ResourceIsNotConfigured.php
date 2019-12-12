<?php declare(strict_types=1);

namespace Somnambulist\ProjectManager\Exceptions;

use InvalidArgumentException;

/**
 * Class ResourceIsNotConfigured
 *
 * @package Somnambulist\ProjectManager\Exceptions
 * @subpackage Somnambulist\ProjectManager\Exceptions\ResourceIsNotConfigured
 */
class ResourceIsNotConfigured extends InvalidArgumentException
{

    public static function raise(string $name)
    {
        return new static(sprintf('<error> ERR </error> Library <comment>%s</comment> not found!', $name));
    }
}
