<?php declare(strict_types=1);

namespace Somnambulist\ProjectManager\Models;

use function getenv;

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
        return sprintf('%s/%s%s', getenv('PROJECT_DIR'), getenv('PROJECT_LIBRARY_PREFIX') ?: '', $this->directoryName());
    }
}
