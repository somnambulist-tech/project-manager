<?php declare(strict_types=1);

namespace Somnambulist\ProjectManager\Tests\Models\Docker\Components;

use PHPUnit\Framework\TestCase;
use Somnambulist\ProjectManager\Services\Docker\Factories\ComposeServiceFactory;
use Symfony\Component\Yaml\Yaml;

/**
 * Class ComposeServiceTest
 *
 * @package    Somnambulist\ProjectManager\Tests\Models\Docker\Components
 * @subpackage Somnambulist\ProjectManager\Tests\Models\Docker\Components\ComposeServiceTest
 */
class ComposeServiceTest extends TestCase
{

    public function testCreate()
    {
        $data = [
            'build'    => [
                'context'    => '.',
                'dockerfile' => 'config/docker/dns/Dockerfile',
                'args'       => [
                    'DNS_HOST_IP'    => '${DNS_HOST_IP:-127.0.0.1}',
                    'PROJECT_DOMAIN' => '${PROJECT_DOMAIN:-example.dev}',
                ],
            ],
            'restart'  => 'always',
            'ports'    => [
                0 => '1034:53/udp',
                1 => '5380:8080',
            ],
            'logging'  => [
                'options' => [
                    'max-size' => '10m',
                ],
            ],
            'networks' => [
                0 => 'backend',
                'net' => [
                    'aliases' => [
                        0 => 'alias1',
                        1 => 'alias2',
                    ],
                ],
            ],
            'labels'   => [
                0 => 'traefik.enable=true',
                1 => 'traefik.http.routers.dns.rule=Host(`dns.${PROJECT_DOMAIN:-example.dev}`)',
                2 => 'traefik.http.routers.dns.tls=true',
                3 => 'traefik.http.services.dns.loadbalancer.server.port=8080',
            ],
            'healthcheck' => [
                'test' => ['CMD' , 'test']
            ]
        ];

        $service = (new ComposeServiceFactory())->from('dns', $data);

        $opts = Yaml::DUMP_OBJECT_AS_MAP | Yaml::DUMP_EMPTY_ARRAY_AS_SEQUENCE | Yaml::DUMP_NULL_AS_TILDE;

        $this->assertEquals($this->expected(), Yaml::dump($service->exportForYaml(), 4, 4, $opts));
    }

    private function expected(): string
    {
        return <<<YAML
build:
    context: .
    dockerfile: config/docker/dns/Dockerfile
    args:
        DNS_HOST_IP: '\${DNS_HOST_IP:-127.0.0.1}'
        PROJECT_DOMAIN: '\${PROJECT_DOMAIN:-example.dev}'
restart: always
labels:
    traefik.enable: 'true'
    traefik.http.routers.dns.rule: 'Host(`dns.\${PROJECT_DOMAIN:-example.dev}`)'
    traefik.http.routers.dns.tls: 'true'
    traefik.http.services.dns.loadbalancer.server.port: '8080'
ports:
    - '1034:53/udp'
    - '5380:8080'
networks:
    backend: ~
    net:
        aliases:
            - alias1
            - alias2
healthcheck:
    test:
        - CMD
        - test
logging:
    options:
        max-size: 10m

YAML;

    }
}
