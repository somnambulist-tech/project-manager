<?php declare(strict_types=1);

namespace Somnambulist\ProjectManager\Models\Definitions;

use Somnambulist\Collection\MutableCollection;
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

    /**
     * @var string
     */
    private $service;

    /**
     * @var string
     */
    private $template;

    /**
     * @var MutableCollection|ServiceDefinition[]
     */
    private $files;

    public function __construct(string $service, string $template, array $files = [])
    {
        $this->service       = $service;
        $this->template      = $template;
        $this->files         = new MutableCollection($files);
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
