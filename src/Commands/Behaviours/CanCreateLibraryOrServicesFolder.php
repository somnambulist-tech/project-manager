<?php declare(strict_types=1);

namespace Somnambulist\ProjectManager\Commands\Behaviours;

use Somnambulist\ProjectManager\Services\Console\ConsoleHelper;
use function mkdir;

/**
 * Trait CanCreateLibraryOrServicesFolder
 *
 * @package    Somnambulist\ProjectManager\Commands\Behaviours
 * @subpackage Somnambulist\ProjectManager\Commands\Behaviours\CanCreateLibraryOrServicesFolder
 *
 * @method ConsoleHelper tools()
 */
trait CanCreateLibraryOrServicesFolder
{

    protected function createLibraryFolder(string $cwd): int
    {
        if (!mkdir($cwd, 0775, true)) {
            $this->tools()->error('unable to create folder at <comment>%s</comment>', $cwd);
            $this->tools()->newline();

            return 1;
        }

        return 0;
    }
}
