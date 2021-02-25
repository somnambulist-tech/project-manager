<?php declare(strict_types=1);

namespace Somnambulist\ProjectManager\Commands\Behaviours;

use Somnambulist\ProjectManager\Contracts\RunnableResource;
use Somnambulist\ProjectManager\Models\Project;
use Somnambulist\ProjectManager\Services\Console\ConsoleHelper;
use Symfony\Component\Console\Input\InputInterface;

/**
 * Trait CanSelectServiceFromInput
 *
 * @package    Somnambulist\ProjectManager\Commands\Behaviours
 * @subpackage Somnambulist\ProjectManager\Commands\Behaviours\CanSelectServiceFromInput
 *
 * @method ConsoleHelper tools()
 */
trait CanSelectServiceFromInput
{

    protected function getServiceSelectionFromInput(InputInterface $input, Project $project): ?RunnableResource
    {
        if (null === $service = $input->getArgument('service')) {
            $libs = $project->services()->list()->sortBy('key')->keys();

            $service = $this->tools()->choose('Select the service to work with: ', $libs->toArray());
        }

        if ((null === $resource = $project->getLibrary($service)) || !$resource instanceof RunnableResource) {
            $this->tools()->error('did not find <info>%s</info> in services', $service);
            $this->tools()->newline();

            return null;
        }

        return $resource;
    }
}
