<?php declare(strict_types=1);

namespace Somnambulist\ProjectManager\Services\Docker;

use Somnambulist\Collection\MutableCollection;
use Somnambulist\ProjectManager\Exceptions\DefinitionNotFound;
use Somnambulist\ProjectManager\Models\Definitions\ServiceDefinition;
use SplFileInfo;
use Symfony\Component\Finder\Exception\DirectoryNotFoundException;
use Symfony\Component\Finder\Finder;
use function dirname;
use function sprintf;
use function str_replace;

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
        $files = (new Finder())->files()->in(dirname(__DIR__, 3) . '/config/definitions')->depth(0)->name('*.yaml')->sortByName();

        foreach ($files as $file) {
            $name = $file->getBasename('.yaml');
            $ret->set($name, new ServiceDefinition($name, $file->getContents(), $this->findRelatedFilesForService($name, $file)));
        }

        return $ret;
    }

    private function findExternalDefinitions(): MutableCollection
    {
        $ret   = new MutableCollection();
        $files = (new Finder())->files()->ignoreDotFiles(true)->ignoreVCS(true)->name('*.yaml')->sortByName();
        $f     = false;

        foreach ([$_SERVER['SOMNAMBULIST_ACTIVE_PROJECT'] . '/definitions', '/definitions'] as $path) {
            try {
                $files->in($d = sprintf('%s/%s', $_SERVER['SOMNAMBULIST_PROJECTS_CONFIG_DIR'], $path));
                $f = true;
            } catch (DirectoryNotFoundException $e) {
                // SF Finder throws exceptions if the dir does not exist
            }
        }

        if ($f) {
            foreach ($files as $file) {
                $name = $file->getBasename('.yaml');
                $ret->set($name, new ServiceDefinition($name, $file->getContents(), $this->findRelatedFilesForService($name, $file)));
            }
        }

        return $ret;
    }

    private function findRelatedFilesForService(string $name, SplFileInfo $file): array
    {
        $path   = sprintf('%s/%s', $file->getPath(), $name);
        $return = [];

        try {
            $files = (new Finder())->files()->in($path)->name('*')->ignoreVCS(true)->ignoreDotFiles(true);

            foreach ($files as $f) {
                $return[] = new ServiceDefinition(str_replace($path . '/', '', $f->getPathname()), $f->getContents());
            }
        } catch (DirectoryNotFoundException $e) {
        }

        return $return;
    }
}
