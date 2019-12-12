<?php declare(strict_types=1);

namespace Somnambulist\ProjectManager\Commands\Behaviours;

/**
 * Trait UseEnvironmentTemplate
 *
 * @package    Somnambulist\ProjectManager\Commands\Behaviours
 * @subpackage Somnambulist\ProjectManager\Commands\Behaviours\UseEnvironmentTemplate
 */
trait UseEnvironmentTemplate
{

    private function environmentTemplate(string $project = null, string $dir = null, string $libraries = null, string $services = null)
    {
        return <<<ENV

SOMNAMBULIST_ACTIVE_PROJECT=$project

PROJECT_DIR=$dir
PROJECT_LIBRARIES_DIR=$libraries
PROJECT_SERVICES_DIR=$services

ENV;
    }
}
