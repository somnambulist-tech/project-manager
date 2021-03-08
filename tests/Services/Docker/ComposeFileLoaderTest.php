<?php declare(strict_types=1);

namespace Somnambulist\ProjectManager\Tests\Services\Docker;

use Somnambulist\ProjectManager\Models\Docker\Components\Build;
use Somnambulist\ProjectManager\Models\Docker\Components\ComposeNetwork;
use Somnambulist\ProjectManager\Models\Docker\Components\ComposeService;
use Somnambulist\ProjectManager\Models\Docker\Components\ComposeVolume;
use Somnambulist\ProjectManager\Services\Docker\ComposeFileLoader;
use PHPUnit\Framework\TestCase;
use function file_get_contents;

/**
 * Class ComposeFileLoaderTest
 *
 * @package    Somnambulist\ProjectManager\Tests\Services\Docker
 * @subpackage Somnambulist\ProjectManager\Tests\Services\Docker\ComposeFileLoaderTest
 */
class ComposeFileLoaderTest extends TestCase
{

    public function testLoad()
    {
        $loader = new ComposeFileLoader();
        $dc = $loader->load(__DIR__ . '/../../Stubs/config/docker/docker-compose.yml');

        $this->assertEquals('3.7', $dc->version());
        $this->assertCount(1, $dc->networks());
        $this->assertCount(4, $dc->volumes());
        $this->assertCount(6, $dc->services());

        $this->assertArrayHasKey('db-accounts', $dc->services());
        $this->assertArrayHasKey('db-events', $dc->services());
        $this->assertArrayHasKey('proxy', $dc->services());
        $this->assertArrayHasKey('dns', $dc->services());
        $this->assertArrayHasKey('rabbitmq', $dc->services());
        $this->assertArrayHasKey('syslog', $dc->services());
    }

    public function testParse()
    {
        $loader = new ComposeFileLoader();
        $dc = $loader->parse(file_get_contents(__DIR__ . '/../../Stubs/config/docker/docker-compose.yml'));

        $this->assertEquals('3.7', $dc->version());
        $this->assertCount(1, $dc->networks());
        $this->assertCount(4, $dc->volumes());
        $this->assertCount(6, $dc->services());

        $this->assertArrayHasKey('db-accounts', $dc->services());
        $this->assertArrayHasKey('db-events', $dc->services());
        $this->assertArrayHasKey('proxy', $dc->services());
        $this->assertArrayHasKey('dns', $dc->services());
        $this->assertArrayHasKey('rabbitmq', $dc->services());
        $this->assertArrayHasKey('syslog', $dc->services());
    }

    public function testLoadResolvesServiceData()
    {
        $loader = new ComposeFileLoader();
        $dc = $loader->load(__DIR__ . '/../../Stubs/config/docker/docker-compose.yml');

        $service = $dc->services()->get('proxy');

        $this->assertInstanceOf(ComposeService::class, $service);

        $this->assertCount(1, $service->networks());
        $this->assertCount(1, $service->volumes());
        $this->assertCount(4, $service->labels());
        $this->assertCount(10, $service->command());
        $this->assertCount(3, $service->ports());

        $this->assertInstanceOf(Build::class, $service->build());

        $dns = $dc->services()->get('dns');

        $this->assertCount(2, $dns->build()->args());

        $syslog = $dc->services()->get('syslog');

        $this->assertCount(3, $syslog->ports());
        $this->assertEquals('udp', $syslog->ports()->get(0)->protocol());
    }

    public function testLoadResolvesNetworkData()
    {
        $loader = new ComposeFileLoader();
        $dc = $loader->load(__DIR__ . '/../../Stubs/config/docker/docker-compose.yml');

        $net = $dc->networks()->get('backend');

        $this->assertInstanceOf(ComposeNetwork::class, $net);

        $this->assertEquals('mycompany_network_backend', $net->name());
        $this->assertFalse($net->isExternal());
        $this->assertEquals('bridge', $net->driver());
    }

    public function testLoadResolvesVolumeData()
    {
        $loader = new ComposeFileLoader();
        $dc = $loader->load(__DIR__ . '/../../Stubs/config/docker/docker-compose.yml');

        $vol = $dc->volumes()->get('syslog_logs');

        $this->assertInstanceOf(ComposeVolume::class, $vol);

        $this->assertEquals('mycompany_volumes_syslog-logs', $vol->name());
        $this->assertEquals('local', $vol->driver());
    }
}
