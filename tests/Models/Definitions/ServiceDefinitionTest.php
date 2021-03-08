<?php declare(strict_types=1);

namespace Somnambulist\ProjectManager\Tests\Models\Definitions;

use PHPUnit\Framework\TestCase;
use Somnambulist\ProjectManager\Models\Definitions\ServiceDefinition;
use function file_get_contents;

/**
 * Class ServiceDefinitionTest
 *
 * @package    Somnambulist\ProjectManager\Tests\Models\Definitions
 * @subpackage Somnambulist\ProjectManager\Tests\Models\Definitions\ServiceDefinitionTest
 */
class ServiceDefinitionTest extends TestCase
{

    public function testCreate()
    {
        $def = new ServiceDefinition('service', $t = file_get_contents(__DIR__ . '/../../../config/definitions/mariadb.yaml'));

        $this->assertEquals('service', $def->service());
        $this->assertEquals($t, $def->template());
    }

    public function testDefinitionParameters()
    {
        $def = new ServiceDefinition('service', $t = file_get_contents(__DIR__ . '/../../../config/definitions/mariadb.yaml'));

        $expected = [
            '{SPM::EXTERNAL_PORT}',
            '{SPM::NETWORK_NAME}',
            '{SPM::PROJECT_NAME}',
            '{SPM::SERVICE_NAME}',
        ];

        $this->assertEquals($expected, $def->parameters());
    }

    public function testDefinitionParametersFromMultipleFiles()
    {
        $def = new ServiceDefinition('service', file_get_contents(__DIR__ . '/../../../config/definitions/nginx.yaml'), [
            new ServiceDefinition('Dockerfile', file_get_contents(__DIR__ . '/../../../config/definitions/nginx/Dockerfile')),
            new ServiceDefinition('site.conf', file_get_contents(__DIR__ . '/../../../config/definitions/nginx/site.conf')),
        ]);

        $expected = [
            '{SPM::NETWORK_NAME}',
            '{SPM::SERVICE_APP_PORT}',
            '{SPM::SERVICE_APP}',
            '{SPM::SERVICE_HOST}',
            '{SPM::SERVICE_NAME}',
            '{SPM::SERVICE_PORT}',
        ];

        $this->assertEquals($expected, $def->parameters());
    }

    public function testDefinition()
    {
        $def = new ServiceDefinition('redis', $t = file_get_contents(__DIR__ . '/../../../config/definitions/redis.yaml'));
        $params = [
            '{SPM::SERVICE_NAME}' => 'sample-test',
            '{SPM::NETWORK_NAME}' => 'mycompany_network',
        ];

        $expected = <<<DEF
sample-test:
    image: 'redis:alpine'
    networks:
        - mycompany_network
    healthcheck:
        test: ["CMD", "redis-cli", "ping"]

DEF;

        $this->assertEquals($expected, $def->createServiceDefinitionUsing($params));
    }
}
