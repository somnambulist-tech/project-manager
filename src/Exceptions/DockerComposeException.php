<?php declare(strict_types=1);

namespace Somnambulist\ProjectManager\Exceptions;

/**
 * Class DockerComposeException
 *
 * @package    Somnambulist\ProjectManager\Exceptions
 * @subpackage Somnambulist\ProjectManager\Exceptions\DockerComposeException
 */
class DockerComposeException extends ValidationException
{

    public static function serviceVolumeNotDefined(array $volumes): self
    {
        return new self(sprintf('One or more service volumes (%s) have not been defined', implode(', ', $volumes)));
    }

    public static function serviceNetworkNotDefined(array $networks): self
    {
        return new self(sprintf('One or more service networks (%s) have not been defined', implode(', ', $networks)));
    }
}
