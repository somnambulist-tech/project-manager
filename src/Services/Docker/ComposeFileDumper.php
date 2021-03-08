<?php declare(strict_types=1);

namespace Somnambulist\ProjectManager\Services\Docker;

use Somnambulist\ProjectManager\Models\Docker\DockerCompose;
use Symfony\Component\Yaml\Yaml;
use function array_map;
use function explode;
use function file_put_contents;
use function implode;
use function preg_match_all;
use function sprintf;

/**
 * Class ComposeFileDumper
 *
 * @package    Somnambulist\ProjectManager\Services\Docker
 * @subpackage Somnambulist\ProjectManager\Services\Docker\ComposeFileDumper
 */
class ComposeFileDumper
{

    public function store(DockerCompose $compose, string $file): bool
    {
        $opts = Yaml::DUMP_OBJECT_AS_MAP | Yaml::DUMP_EMPTY_ARRAY_AS_SEQUENCE | Yaml::DUMP_NULL_AS_TILDE;
        $yaml = Yaml::dump($compose->exportForYaml(), 5, 4, $opts);

        $matches = [];
        $replace = [' []' => '', ': ~' => ':'];

        preg_match_all('/: \[(.+)]/', $yaml, $matches);
        foreach ($matches[1] as $match) {
            $replace[$match] = sprintf('"%s"', implode('", "', array_map('trim', explode(',', $match))));
        }

        $yaml = strtr($yaml, $replace);

        return false !== file_put_contents($file, $yaml);
    }
}
