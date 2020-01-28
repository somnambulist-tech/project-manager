<?php declare(strict_types=1);

namespace Somnambulist\ProjectManager\Models;

use Somnambulist\Collection\FrozenCollection;
use Somnambulist\ProjectManager\Contracts\TemplatableResource;
use function array_unique;
use function sort;

/**
 * Class Config
 *
 * @package Somnambulist\ProjectManager\Models
 * @subpackage Somnambulist\ProjectManager\Models\Config
 */
class Config implements TemplatableResource
{

    /**
     * @var FrozenCollection
     */
    private $config;

    /**
     * @var FrozenCollection
     */
    private $parameters;

    /**
     * @var Projects
     */
    private $projects;

    /**
     * @var Templates
     */
    private $templates;

    /**
     * Constructor
     *
     * @param array $config
     * @param array $parameters
     */
    public function __construct(array $config = [], array $parameters = [])
    {
        $this->config     = new FrozenCollection($config);
        $this->parameters = new FrozenCollection($parameters);
        $this->projects   = new Projects();
        $this->templates  = new Templates();
    }

    public function home(): string
    {
        return $_SERVER['SOMNAMBULIST_PROJECTS_CONFIG_DIR'];
    }

    public function projectsDir(): string
    {
        return $this->config->value('projects_dir', 'Projects');
    }

    public function active(): string
    {
        return $_SERVER['SOMNAMBULIST_ACTIVE_PROJECT'] ?? '';
    }

    public function config(): FrozenCollection
    {
        return $this->config;
    }

    public function parameters(): FrozenCollection
    {
        return $this->parameters;
    }

    public function projects(): Projects
    {
        return $this->projects;
    }

    public function templates(): Templates
    {
        return $this->templates;
    }

    public function availableTemplates(string $type): array
    {
        $templates = array_unique(array_merge(
            $this->templates->for($type),
            $this->projects->active()->templates()->for($type)
        ));

        sort($templates);

        return $templates;
    }
}
