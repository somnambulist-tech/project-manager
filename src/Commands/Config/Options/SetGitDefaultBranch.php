<?php declare(strict_types=1);

namespace Somnambulist\ProjectManager\Commands\Config\Options;

use Somnambulist\ProjectManager\Commands\Config\AbstractOption;
use Somnambulist\ProjectManager\Commands\Config\OptionResult;
use Somnambulist\ProjectManager\Exceptions\ResourceNotFound;
use Somnambulist\ProjectManager\Models\Project;

/**
 * Class SetGitDefaultBranch
 *
 * @package    Somnambulist\ProjectManager\Commands\Config\Options
 * @subpackage Somnambulist\ProjectManager\Commands\Config\Options\SetGitDefaultBranch
 */
class SetGitDefaultBranch extends AbstractOption
{

    public function __construct()
    {
        $this->option      = 'git:branch';
        $this->description = 'Set the default branch for the project/library/service';
        $this->scope       = self::SCOPE_ALL_LIBRARIES;
        $this->questions   = [
            'branch' => 'Enter the branch name that will be set as the default:',
        ];
    }

    public function run(Project $project, string $library, array $options): OptionResult
    {
        $resource = 'project' === $library ? $project : null;

        if (!isset($options['branch']) || empty($options['branch'])) {
            return OptionResult::error('missing a value for <info>branch</info>');
        }

        if (!$resource && null === $resource = $project->getLibrary($library)) {
            throw ResourceNotFound::raise($library);
        }

        $resource->setBranch($options['branch']);

        return OptionResult::ok();
    }
}
