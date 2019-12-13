<?php declare(strict_types=1);

namespace Somnambulist\ProjectManager\Services\Config;

use IlluminateAgnostic\Str\Support\Str;
use RuntimeException;
use Somnambulist\ProjectManager\Models\Library;
use Somnambulist\ProjectManager\Models\Project;
use Somnambulist\ProjectManager\Models\Service;
use Somnambulist\ProjectManager\Models\Template;
use Symfony\Component\Yaml\Yaml;
use function file_put_contents;

/**
 * Class ExportProjectToYaml
 *
 * @package    Somnambulist\ProjectManager\Services\Config
 * @subpackage Somnambulist\ProjectManager\Services\Config\ExportProjectToYaml
 */
class ExportProjectToYaml
{

    public function export(Project $project, $file): bool
    {
        $arr = [
            'somnambulist' => [
                'project'   => [
                    'name'              => $project->name(),
                    'working_dir'       => Str::replaceFirst($_SERVER['HOME'], '${HOME}', $project->workingPath()),
                    'repository'        => $project->repository(),
                    'libraries_dirname' => $project->librariesName(),
                    'services_dirname'  => $project->servicesName(),
                ],
                'docker'    => $project->docker()->toArray(),
                'libraries' => [],
                'services'  => [],
                'templates' => [],
            ],
        ];

        $this->attachLibraries($project, $arr);
        $this->attachServices($project, $arr);
        $this->attachTemplates($project, $arr);

        $yaml = Yaml::dump($arr, 5, 4, Yaml::DUMP_NULL_AS_TILDE);

        if (false === file_put_contents($file, $yaml)) {
            throw new RuntimeException(sprintf('Failed to create yaml file at "%s"', $file));
        }

        return true;
    }

    private function attachTemplates(Project $project, array &$arr): void
    {
        $project->templates()->list()->each(function (Template $template) use (&$arr) {
            $arr['somnambulist']['templates'][$template->type()][$template->name()] = $template->source();
        });
    }

    private function attachLibraries(Project $project, array &$arr): void
    {
        $project->libraries()->list()->each(function (Library $library) use (&$arr) {
            $arr['somnambulist']['libraries'][$library->name()] = [
                'repository' => $library->repository(),
                'dirname'    => $library->directoryName(),
            ];
        });
    }

    private function attachServices(Project $project, array &$arr): void
    {
        $project->services()->list()->each(function (Service $service) use (&$arr) {
            $arr['somnambulist']['services'][$service->name()] = [
                'repository'    => $service->repository(),
                'dirname'       => $service->directoryName(),
                'app_container' => $service->appContainer(),
                'dependencies'  => $service->dependencies()->toArray(),
            ];
        });
    }
}
