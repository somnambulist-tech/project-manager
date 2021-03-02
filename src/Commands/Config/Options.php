<?php declare(strict_types=1);

namespace Somnambulist\ProjectManager\Commands\Config;

use Somnambulist\Collection\MutableCollection;
use Somnambulist\ProjectManager\Models\Project;
use function sprintf;

/**
 * Class Options
 *
 * @package    Somnambulist\ProjectManager\Commands\Config
 * @subpackage Somnambulist\ProjectManager\Commands\Config\Options
 */
class Options
{

    /**
     * @var MutableCollection
     */
    private $options;

    public function __construct()
    {
        $this->options = new MutableCollection();

        $this->addOptions([
            new Options\AddServiceDependency(),
            new Options\AddProjectTemplate(),
            new Options\RemoveProjectTemplate(),
            new Options\RenameService(),
            new Options\RemoveServiceDependency(),
            new Options\SetGitDefaultBranch(),
            new Options\SetGitRemoteRepository(),
            new Options\SetDockerNetworkName(),
            new Options\SetDockerProjectName(),
            new Options\SetServiceContainerName(),
        ]);

        $this->options->sortBy('key');
    }

    private function addOptions(array $options): void
    {
        foreach ($options as $option) {
            $this->addOption($option);
        }
    }

    private function addOption(AbstractOption $option): void
    {
        $this->options->set($option->getOption(), $option);
    }

    public function describe(): string
    {
        return $this
            ->options
            ->map(function (AbstractOption $o) {
                return sprintf("<info>%- 25s</info> : %s\n", $o->getOption(), $o->getDescription());
            })
            ->implode('')
        ;
    }

    public function list(): array
    {
        return $this->options->keys()->toArray();
    }

    public function get(string $option): AbstractOption
    {
        return $this->options->get($option, new class extends AbstractOption {
            public function __construct()
            {
                $this->option = 'noop';
            }

            public function run(Project $project, string $library, array $options): OptionResult
            {
                return OptionResult::ok();
            }
        });
    }
}
