<?php declare(strict_types=1);

namespace Somnambulist\ProjectManager\Services\Config;

use Somnambulist\Collection\MutableCollection;
use Somnambulist\ProjectManager\Models\Config;
use Somnambulist\ProjectManager\Models\Library;
use Somnambulist\ProjectManager\Models\Project;
use Somnambulist\ProjectManager\Models\Service;
use Somnambulist\ProjectManager\Models\Template;
use Symfony\Component\Yaml\Yaml;
use function array_combine;
use function array_merge;
use function dirname;
use function file_get_contents;
use function getenv;
use function glob;
use function is_array;
use function ksort;
use function sprintf;
use function strtoupper;
use const DIRECTORY_SEPARATOR;

/**
 * Class ConfigParser
 *
 * @package Somnambulist\ProjectManager\Services\Config
 * @subpackage Somnambulist\ProjectManager\Services\Config\ConfigParser
 */
class ConfigParser
{

    /**
     * Converts a Yaml file into a Config object, replacing params
     *
     * Config contains several sets of processed data objects, and handles locating all
     * project configurations on the current system.
     *
     * @param string $file
     *
     * @return Config
     */
    public function parse(string $file): Config
    {
        $config = MutableCollection::create(Yaml::parse($this->readFile($file))['somnambulist']);

        $spm = new Config($config->except('templates')->toArray(), $this->getEnvParameters());

        $config->value('templates', new MutableCollection())->each(function ($templates, $type) use ($spm) {
            if (is_array($templates)) {
                foreach ($templates as $name => $source) {
                    $spm->templates()->add(new Template($name, $type, $source));
                }
            }
        });

        $this->locateProjects($spm);

        return $spm;
    }

    /**
     * Converts a single project.yaml file into a Project object
     *
     * @param string $file
     *
     * @return Project
     */
    public function project(string $file): Project
    {
        return $this->createProject($file);
    }

    private function readFile(string $file): string
    {
        return $this->replaceVars(file_get_contents($file));
    }

    private function locateProjects(Config $spm): void
    {
        $entries = glob($_SERVER['SOMNAMBULIST_PROJECTS_CONFIG_DIR'] . DIRECTORY_SEPARATOR . '*' . DIRECTORY_SEPARATOR . 'project.yaml');

        foreach ($entries as $entry) {
            $spm->projects()->add($this->createProject($entry));
        }
    }

    private function createProject(string $file): Project
    {
        $config = MutableCollection::create(Yaml::parse($this->readFile($file)));

        $project = new Project(
            $config->get('somnambulist.project.name'),
            dirname($file),
            $config->get('somnambulist.project.working_dir'),
            $config->get('somnambulist.project.services_dirname'),
            $config->get('somnambulist.project.libraries_dirname'),
            $config->get('somnambulist.project.repository'),
            $config->get('somnambulist.docker', new MutableCollection())->toArray(),
        );

        $this->createLibraries($project, $config);
        $this->createServices($project, $config);
        $this->createTemplates($project, $config);

        return $project;
    }

    private function createLibraries(Project $project, MutableCollection $config): void
    {
        $config->value('somnambulist.libraries', new MutableCollection())->each(function ($library, $name) use ($project) {
            $project->libraries()->add(
                new Library(
                    $name,
                    $library['dirname'],
                    $library['repository']
                )
            );
        });
    }

    private function createServices(Project $project, MutableCollection $config): void
    {
        $config->value('somnambulist.services', new MutableCollection())->each(function ($service, $name) use ($project) {
            $project->services()->add(
                new Service(
                    $name,
                    $service['dirname'],
                    $service['repository'],
                    $service['app_container'],
                    $service['dependencies'] ?? [],
                )
            );
        });
    }

    private function createTemplates(Project $project, MutableCollection $config): void
    {
        $config->value('somnambulist.templates', new MutableCollection())->each(function ($templates, $type) use ($project) {
            if (is_array($templates)) {
                foreach ($templates as $name => $source) {
                    $project->templates()->add(new Template($name, $type, $source));
                }
            }
        });
    }

    private function getEnvParameters(): array
    {
        $gEnv = MutableCollection::collect($_SERVER)->except('SYMFONY_DOTENV_VARS', 'PATH', 'argv', 'argc')->removeNulls();
        $pEnv = MutableCollection::collect(getenv())->except('SYMFONY_DOTENV_VARS', 'PATH', 'argv', 'argc')->removeNulls();

        $params = array_merge(
            array_combine(
                $pEnv->keys()->map(function ($value) {return sprintf('${%s}', strtoupper($value)); })->toArray(),
                $pEnv->values()->toArray()
            ),
            array_combine(
                $gEnv->keys()->map(function ($value) {return sprintf('${%s}', strtoupper($value)); })->toArray(),
                $gEnv->values()->toArray()
            )
        );

        ksort($params);

        return $params;
    }

    private function replaceVars(string $config): string
    {
        return strtr(
            $config,
            $this->getEnvParameters()
        );
    }
}
