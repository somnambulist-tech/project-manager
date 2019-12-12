<?php declare(strict_types=1);

namespace Somnambulist\ProjectManager\Exceptions;

use InvalidArgumentException;

/**
 * Class ResourceAlreadyInstalled
 *
 * @package Somnambulist\ProjectManager\Exceptions
 * @subpackage Somnambulist\ProjectManager\Exceptions\ResourceAlreadyInstalled
 */
class ResourceAlreadyInstalled extends InvalidArgumentException
{

    public static function raise(string $name)
    {
        return new static(sprintf('Library <info>%s</info> is already installed', $name));
    }
}
