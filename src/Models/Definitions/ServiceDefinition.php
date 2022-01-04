<?php declare(strict_types=1);

namespace Somnambulist\ProjectManager\Models\Definitions;

use Somnambulist\Components\Collection\MutableCollection;
use function preg_match_all;
use function strtr;

/**
 * Class ServiceDefinition
 *
 * @package    Somnambulist\ProjectManager\Services\Docker
 * @subpackage Somnambulist\ProjectManager\Models\Definitions\ServiceDefinition
 */
class ServiceDefinition
{
    private MutableCollection $files;

    public function __construct(private string $service, private string $template, array $files = [])
    {
        $this->files = new MutableCollection($files);
    }

    public function name(): string
    {
        return $this->service();
    }

    public function service(): string
    {
        return $this->service;
    }

    public function template(): string
    {
        return $this->template;
    }

    public function files(): MutableCollection
    {
        return $this->files;
    }

    public function parameters(): array
    {
        $subs    = $this->files->map->parameters()->flatten();
        $matches = [];

        preg_match_all('/({SPM::[\w\d]+})/', $this->template, $matches);

        return $subs->merge($matches[1])->unique()->sortBy('value')->values()->toArray();
    }

    public function createServiceDefinitionUsing(array $parameters): string
    {
        return strtr($this->template, $parameters);
    }
}
