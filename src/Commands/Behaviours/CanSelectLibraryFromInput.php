<?php declare(strict_types=1);

namespace Somnambulist\ProjectManager\Commands\Behaviours;

use Somnambulist\ProjectManager\Contracts\InstallableResource;
use Somnambulist\ProjectManager\Models\Project;
use Somnambulist\ProjectManager\Services\Console\ConsoleHelper;
use Symfony\Component\Console\Input\InputInterface;
use function str_replace;
use function trim;

/**
 * Trait CanSelectLibraryFromInput
 *
 * @package    Somnambulist\ProjectManager\Commands\Behaviours
 * @subpackage Somnambulist\ProjectManager\Commands\Behaviours\CanSelectLibraryFromInput
 *
 * @method ConsoleHelper tools()
 */
trait CanSelectLibraryFromInput
{

    protected function getLibrarySelectionFromInput(InputInterface $input, Project $project): ?InstallableResource
    {
        if (null === $library = $input->getArgument('library')) {
            $libs = $project->getListOfLibraries();

            $library = trim(
                str_replace(
                    ['(lib)', '(service)'], '', $this->tools()->choose('Select the library/service to open: ', $libs->toArray())
                )
            );
        }

        if (null === $resource = $project->getLibrary($library)) {
            $this->tools()->error('did not find <info>%s</info> in libraries or services', $library);
            $this->tools()->newline();

            return null;
        }

        return $resource;
    }
}
