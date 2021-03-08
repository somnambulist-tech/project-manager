<?php declare(strict_types=1);

namespace Somnambulist\ProjectManager\Services\Docker;

use Somnambulist\Collection\MutableCollection;
use Somnambulist\ProjectManager\Exceptions\DefinitionNotFound;
use Somnambulist\ProjectManager\Models\Definitions\ServiceDefinition;
use function basename;
use function dirname;
use function file_get_contents;
use function glob;
use function is_dir;
use function str_replace;
use const GLOB_BRACE;

/**
 * Class ServiceDefinitionLocator
 *
 * @package    Somnambulist\ProjectManager\Services\Docker
 * @subpackage Somnambulist\ProjectManager\Services\Docker\ServiceDefinitionLocator
 */
class ServiceDefinitionLocator
{

    public function find(string $service): ServiceDefinition
    {
        if (null === $def = $this->findAll()->get($service)) {
            throw DefinitionNotFound::raise($service);
        }

        return $def;
    }

    public function findAll(): MutableCollection
    {
        return $this->findInternalDefinitions()->merge($this->findExternalDefinitions())->sortBy('key');
    }

    private function findInternalDefinitions(): MutableCollection
    {
        $ret   = new MutableCollection();
        $files = glob(dirname(__DIR__, 3) . '/config/definitions/*.yaml');

        foreach ($files as $file) {
            $name = basename($file, '.yaml');
            $ret->set($name, new ServiceDefinition($name, file_get_contents($file), $this->findRelatedFilesForService($name, $file)));
        }

        return $ret;
    }

    private function findExternalDefinitions(): MutableCollection
    {
        $ret   = new MutableCollection();
        $files = glob($p = $_SERVER['SOMNAMBULIST_PROJECTS_CONFIG_DIR'] . '/definitions/*.yaml');

        foreach ($files as $file) {
            $name = basename($file, '.yaml');
            $ret->set($name, new ServiceDefinition($name, file_get_contents($file), $this->findRelatedFilesForService($name, $file)));
        }

        return $ret;
    }

    private function findRelatedFilesForService(string $name, string $file): array
    {
        $root   = sprintf('%s/%s', dirname($file), $name);
        $files  = glob($p = sprintf('%s/{,*/,*/*/}*', $root), GLOB_BRACE);
        $return = [];

        foreach ($files as $f) {
            if (is_dir($f)) continue;

            $return[] = new ServiceDefinition(str_replace($root . '/', '', $f), file_get_contents($f));
        }

        return $return;
    }
}
