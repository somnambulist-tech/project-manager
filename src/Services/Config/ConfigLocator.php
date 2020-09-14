<?php declare(strict_types=1);

namespace Somnambulist\ProjectManager\Services\Config;

use function dirname;
use function getenv;
use function realpath;

/**
 * Class ConfigLocator
 *
 * @package Somnambulist\ProjectManager\Services\Config
 * @subpackage Somnambulist\ProjectManager\Services\Config\ConfigLocator
 */
class ConfigLocator
{

    const ENV_NAME  = 'SOMNAMBULIST_PROJECTS_CONFIG_DIR';
    const FILE_NAME = 'project_manager.yaml';

    public function locate(): string
    {
        if (false !== $file = $this->tryLocations()) {
            return $file;
        }

        return realpath(dirname(__DIR__) . '/../../config/project_manager.yaml');
    }

    private function tryLocations()
    {
        if (getenv(static::ENV_NAME)) {
            return realpath(sprintf('%s/%s', getenv(static::ENV_NAME), static::FILE_NAME));
        }
        if (isset($_SERVER[static::ENV_NAME])) {
            return realpath(sprintf('%s/%s', $_SERVER[static::ENV_NAME], static::FILE_NAME));
        }
        if (isset($_SERVER['XDG_CONFIG_HOME'])) {
            return realpath(sprintf('%s/spm_projects.d/%s', $_SERVER['XDG_CONFIG_HOME'], static::FILE_NAME));
        }

        return realpath(sprintf('%s/.spm_projects.d/%s', $_SERVER['HOME'], static::FILE_NAME));
    }
}
