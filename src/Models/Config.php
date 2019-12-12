<?php declare(strict_types=1);

namespace Somnambulist\ProjectManager\Models;

use Somnambulist\Collection\FrozenCollection;

/**
 * Class Config
 *
 * @package Somnambulist\ProjectManager\Models
 * @subpackage Somnambulist\ProjectManager\Models\Config
 */
class Config
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
    }

    public function home(): string
    {
        return $_SERVER['SOMNAMBULIST_PROJECTS_CONFIG_DIR'];
    }

    public function projectsDir(): string
    {
        return $this->config->get('projects_dir');
    }

    public function active(): string
    {
        return $_SERVER['SOMNAMBULIST_ACTIVE_PROJECT'];
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
}
