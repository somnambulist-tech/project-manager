<?php declare(strict_types=1);

namespace Somnambulist\ProjectManager\Commands\Behaviours;

use function copy;
use function dirname;
use const DIRECTORY_SEPARATOR;

/**
 * Trait CanCopyFromConfigTemplates
 *
 * @package    Somnambulist\ProjectManager\Commands\Behaviours
 * @subpackage Somnambulist\ProjectManager\Commands\Behaviours\CanCopyFromConfigTemplates
 */
trait CanCopyFromConfigTemplates
{

    protected function copyFromConfigTemplates(string $source, string $dest): bool
    {
        $base = dirname(__DIR__, 3) . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'templates';

        return copy($base . DIRECTORY_SEPARATOR . $source, $dest);
    }
}
