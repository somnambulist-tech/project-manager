<?php declare(strict_types=1);

namespace Somnambulist\ProjectManager\Services\Docker\Factories;

use IlluminateAgnostic\Str\Support\Str;
use Somnambulist\ProjectManager\Models\Docker\Components\Build;
use Somnambulist\ProjectManager\Models\Docker\Components\ComposeService;
use Somnambulist\ProjectManager\Models\Docker\Components\HealthCheck;
use Somnambulist\ProjectManager\Models\Docker\Components\Logging;
use Somnambulist\ProjectManager\Models\Docker\Components\Port;
use Somnambulist\ProjectManager\Models\Docker\Components\ServiceNetwork;
use Somnambulist\ProjectManager\Models\Docker\Components\ServiceVolume;
use Symfony\Component\Yaml\Yaml;
use function array_pop;
use function explode;
use function is_array;
use function is_numeric;
use function is_string;
use function preg_match;

/**
 * Class ComposeServiceFactory
 *
 * @package    Somnambulist\ProjectManager\Services\Docker\Factories
 * @subpackage Somnambulist\ProjectManager\Services\Docker\Factories\ComposeServiceFactory
 */
class ComposeServiceFactory
{

    public function convert(string $yaml): ComposeService
    {
        // yaml string should be by service-name -> options
        $data = Yaml::parse($yaml);

        return $this->from(array_pop($data));
    }

    public function from(array $data): ComposeService
    {
        return new ComposeService(
            $data['container_name'] ?? null,
            $data['image'] ?? null,
            Build::from($data['build'] ?? []),
            $data['restart'] ?? 'no',
            $data['depends_on'] ?? [],
            $data['environment'] ?? [],
            $data['command'] ?? [],
            $this->parsePorts($data['ports'] ?? []),
            $this->parseVolumes($data['volumes'] ?? []),
            $this->parseNetworks($data['networks'] ?? []),
            $this->parseLabels($data['labels'] ?? []),
            HealthCheck::from($data['healthcheck'] ?? []),
            Logging::from($data['logging'] ?? []),
        );
    }

    private function parsePorts(array $data): array
    {
        $ret = [];

        foreach ($data as $datum) {
            if (is_string($datum)) {
                preg_match('/(?P<local>[\d\-]+):(?P<published>[\d\-]+)\/?(?P<proto>tcp|udp)?/', $datum, $matches);

                $datum = [
                    'target'    => (int)$matches['local'],
                    'published' => (int)$matches['published'],
                    'protocol'  => $matches['proto'] ?? 'tcp',
                ];
            }

            $ret[] = Port::from($datum);
        }

        return $ret;
    }

    private function parseVolumes(array $data): array
    {
        $ret = [];

        foreach ($data as $datum) {
            if (is_string($datum)) {
                preg_match('/(?P<source>[\d\w\/\-_~.]+):(?P<target>[\d\w\/\-_.]+):?(?P<mode>ro|rw)?/', $datum, $matches);

                $datum = [
                    'type'      => Str::startsWith($matches['source'], ['~', '/', '.', '\\']) ? 'bind' : 'volume',
                    'source'    => $matches['source'],
                    'target'    => $matches['target'],
                    'read_only' => ($matches['mode'] ?? 'rw') == 'ro',
                ];
            }

            $ret[] = ServiceVolume::from($datum);
        }

        return $ret;
    }

    private function parseNetworks(array $data): array
    {
        $ret = [];

        foreach ($data as $key => $datum) {
            if (is_array($datum)) {
                $ret[$key] = new ServiceNetwork($key, $datum['aliases'] ?? []);
            } else {
                $ret[$datum] = new ServiceNetwork($datum);
            }
        }

        return $ret;
    }

    private function parseLabels(array $data): array
    {
        $ret = [];

        foreach ($data as $key => $datum) {
            if (is_numeric($key)) {
                [$label, $value] = explode('=', $datum, 2);
            } else {
                $label = $key;
                $value = $datum;
            }

            $ret[$label] = $value;
        }

        return $ret;
    }
}
