<?php declare(strict_types=1);

namespace Somnambulist\ProjectManager\Tests\Models\Docker;

use Somnambulist\ProjectManager\Exceptions\DockerComposeException;
use Somnambulist\ProjectManager\Models\Docker\Components\ComposeService;
use Somnambulist\ProjectManager\Models\Docker\Components\ServiceNetwork;
use Somnambulist\ProjectManager\Models\Docker\Components\ServiceVolume;
use Somnambulist\ProjectManager\Models\Docker\DockerCompose;
use PHPUnit\Framework\TestCase;
use Somnambulist\ProjectManager\Services\Docker\ComposeFileLoader;

/**
 * Class DockerComposeTest
 *
 * @package    Somnambulist\ProjectManager\Tests\Models\Docker
 * @subpackage Somnambulist\ProjectManager\Tests\Models\Docker\DockerComposeTest
 */
class DockerComposeTest extends TestCase
{

    public function testValidateChecksServicesVolumesAreDefined()
    {
        $dc = new DockerCompose('3.7');
        $dc->services()
            ->add($s1 = new ComposeService('redis', 'redis:alpine'))
            ->add($s2 = new ComposeService('redis2', 'redis:alpine'))
        ;
        $s1->volumes()->add(new ServiceVolume('volume', 'redis', '/var/lib/redis/data'));
        $s2->volumes()->add(new ServiceVolume('volume', 'redis2', '/var/lib/redis/data'));

        $this->expectException(DockerComposeException::class);
        $this->expectExceptionMessage('One or more service volumes (redis, redis2) have not been defined');

        $dc->validate();
    }

    public function testValidateChecksServicesNetworksAreDefined()
    {
        $dc = new DockerCompose('3.7');
        $dc->services()
            ->add($s1 = new ComposeService('redis', 'redis:alpine'))
            ->add($s2 = new ComposeService('redis2', 'redis:alpine'))
        ;
        $s1->networks()->add(new ServiceNetwork('backend'));
        $s2->networks()->add(new ServiceNetwork('redis'));

        $this->expectException(DockerComposeException::class);
        $this->expectExceptionMessage('One or more service networks (backend, redis) have not been defined');

        $dc->validate();
    }

    public function testExportForYaml()
    {
        $loader = new ComposeFileLoader();
        $dc = $loader->load(__DIR__ . '/../../Stubs/config/docker/docker-compose.yml');

        $ret = $dc->exportForYaml();

        $this->assertIsArray($ret);
        $this->assertArrayHasKey('version', $ret);
        $this->assertArrayHasKey('services', $ret);
        $this->assertArrayHasKey('networks', $ret);
        $this->assertArrayHasKey('volumes', $ret);
    }
}
