<?php declare(strict_types=1);

namespace Somnambulist\ProjectManager\Models\Docker\Components;

/**
 * Class ServiceNetwork
 *
 * @package    Somnambulist\ProjectManager\Models\Docker\Components
 * @subpackage Somnambulist\ProjectManager\Models\Docker\Components\ServiceNetwork
 */
class ServiceNetwork
{

    /**
     * @var string
     */
    private $name;

    /**
     * @var array
     */
    private $aliases;

    public function __construct(string $name, array $aliases = [])
    {
        $this->name    = $name;
        $this->aliases = $aliases;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function aliases(): array
    {
        return $this->aliases;
    }

    public function exportForYaml(): ?array
    {
        if (!empty($this->aliases)) {
            return ['aliases' => $this->aliases];
        }

        return null;
    }
}
