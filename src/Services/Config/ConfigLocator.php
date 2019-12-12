<?php declare(strict_types=1);

namespace Somnambulist\ProjectManager\Services\Config;

use function dirname;
use function getenv;
use function realpath;
use const DIRECTORY_SEPARATOR;

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
            return realpath(getenv(static::ENV_NAME) . DIRECTORY_SEPARATOR . static::FILE_NAME);
        }
        if (isset($_SERVER[static::ENV_NAME])) {
            return realpath($_SERVER[static::ENV_NAME] . DIRECTORY_SEPARATOR . static::FILE_NAME);
        }

        return realpath($_SERVER['HOME'] . DIRECTORY_SEPARATOR . '.spm_projects.d' . DIRECTORY_SEPARATOR . static::FILE_NAME);
    }
}
