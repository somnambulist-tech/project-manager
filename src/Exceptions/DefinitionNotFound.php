<?php declare(strict_types=1);

namespace Somnambulist\ProjectManager\Exceptions;

use InvalidArgumentException;

/**
 * Class DefinitionNotFound
 *
 * @package    Somnambulist\ProjectManager\Exceptions
 * @subpackage Somnambulist\ProjectManager\Exceptions\DefinitionNotFound
 */
class DefinitionNotFound extends InvalidArgumentException
{

    public static function raise(string $name)
    {
        return new static(sprintf('Service definition <info>%s</info> was not found in available definitions', $name));
    }
}
