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

    public static function elementAlreadyDefined(string $type, string $name): self
    {
        return new self(sprintf('A "%s" named "%s" already exists', $type, $name));
    }

    public static function serviceVolumeNotDefined(array $volumes): self
    {
        return new self(sprintf('One or more service volumes (%s) have not been defined', implode(', ', $volumes)));
    }

    public static function serviceNetworkNotDefined(array $networks): self
    {
        return new self(sprintf('One or more service networks (%s) have not been defined', implode(', ', $networks)));
    }

    public static function portAlreadyAssigned($port, string $service): self
    {
        return new self(sprintf('Service "%s" has already registered local port "%s"', $service, $port));
    }
}
