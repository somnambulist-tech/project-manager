<?php declare(strict_types=1);

namespace Somnambulist\ProjectManager\Commands\Config;

use Somnambulist\ProjectManager\Models\Project;
use Somnambulist\ProjectManager\Services\Console\ConsoleHelper;
use function array_filter;
use function array_map;
use function explode;

/**
 * Class AbstractCommandOption
 *
 * @package    Somnambulist\ProjectManager\Commands\Config
 * @subpackage Somnambulist\ProjectManager\Commands\Config\AbstractCommandOption
 */
abstract class AbstractCommandOption
{
    const SCOPE_ALL_LIBRARIES = 'AllLibraries';
    const SCOPE_LIBRARIES     = 'Libraries';
    const SCOPE_SERVICES      = 'Services';
    const SCOPE_PROJECT       = 'Project';

    protected ConsoleHelper $tools;
    protected string $option;
    protected string $description;
    protected string $scope = self::SCOPE_ALL_LIBRARIES;
    protected array $questions = [];

    public function setConsoleHelper(ConsoleHelper $tools): void
    {
        $this->tools = $tools;
    }

    public function getOption(): string
    {
        return $this->option;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getScope(): string
    {
        return $this->scope;
    }

    public function getQuestions(): array
    {
        return $this->questions;
    }

    public function hasQuestions(): bool
    {
        return count($this->questions) > 0;
    }

    protected function arrayFromString(string $string, string $separator = ','): array
    {
        return array_filter(array_map('trim', explode($separator, $string)));
    }

    abstract public function run(Project $project, string $library, array $options): CommandOptionResult;

}
