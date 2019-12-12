<?php declare(strict_types=1);

namespace Somnambulist\ProjectManager\Commands\Behaviours;

use InvalidArgumentException;
use Somnambulist\ProjectManager\Models\Config;
use Somnambulist\ProjectManager\Models\Project;
use Somnambulist\ProjectManager\Services\Console\ConsoleHelper;
use Symfony\Component\Console\Input\InputInterface;
use function sprintf;

/**
 * Trait GetProjectFromInput
 *
 * @package    Somnambulist\ProjectManager\Commands\Behaviours
 * @subpackage Somnambulist\ProjectManager\Commands\Behaviours\GetProjectFromInput
 *
 * @property-read Config $config
 * @method ConsoleHelper tools()
 */
trait GetProjectFromInput
{

    protected function getProjectFrom(InputInterface $input): Project
    {
        if (null === $name = $input->getArgument('project')) {
            $name = $this->tools()->choose('Which project do you want to switch to?', $this->config->projects()->names());
        }

        if (null === $project = $this->config->projects()->get($name)) {
            throw new InvalidArgumentException(sprintf('Project "%s" does not exist or is not configured', $name));
        }

        return $project;
    }
}
