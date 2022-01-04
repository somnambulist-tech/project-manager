<?php declare(strict_types=1);

namespace Somnambulist\ProjectManager\Models;

/**
 * Class Library
 *
 * Represents a repository and a local checkout location.
 *
 * @package Somnambulist\ProjectManager\Models
 * @subpackage Somnambulist\ProjectManager\Models\Library
 */
final class Library extends AbstractLibrary
{
    public function installPath(): string
    {
        return sprintf('%s/%s', $_SERVER['PROJECT_LIBRARIES_DIR'], $this->directoryName());
    }
}
