<?php declare(strict_types=1);

namespace Somnambulist\ProjectManager\Tests\Services\Docker;

use Somnambulist\ProjectManager\Services\Docker\ServiceDefinitionLocator;
use PHPUnit\Framework\TestCase;

/**
 * Class ServiceLocatorTest
 *
 * @package    Somnambulist\ProjectManager\Tests\Services\Docker
 * @subpackage Somnambulist\ProjectManager\Tests\Services\Docker\ServiceLocatorTest
 */
class ServiceLocatorTest extends TestCase
{
    protected function setUp(): void
    {
        if (!isset($_SERVER['SOMNAMBULIST_PROJECTS_CONFIG_DIR'])) {
            $_SERVER['SOMNAMBULIST_PROJECTS_CONFIG_DIR'] = $_SERVER['HOME'] . '/.spm_projects.d';
        }
    }

    public function testFindAll()
    {
        $locator = new ServiceDefinitionLocator();
        $ret = $locator->findAll();

        $this->assertNotCount(0, $ret);
    }

    public function testFindAllLoadsInternalServices()
    {
        $locator = new ServiceDefinitionLocator();
        $ret = $locator->findAll();

        $this->assertArrayHasKey('dnsmasq', $ret);
        $this->assertArrayHasKey('mariadb', $ret);
        $this->assertArrayHasKey('nginx', $ret);
        $this->assertArrayHasKey('php-fpm7', $ret);
        $this->assertArrayHasKey('php-fpm8', $ret);
        $this->assertArrayHasKey('postgres', $ret);
        $this->assertArrayHasKey('rabbitmq', $ret);
        $this->assertArrayHasKey('redis', $ret);
        $this->assertArrayHasKey('syslog', $ret);
        $this->assertArrayHasKey('traefik', $ret);
    }

    public function testFind()
    {
        $locator = new ServiceDefinitionLocator();
        $ret = $locator->find('traefik');

        $this->assertEquals('traefik', $ret->service());
    }

    public function testFindLoadsRelatedFiles()
    {
        $locator = new ServiceDefinitionLocator();
        $ret = $locator->find('traefik');

        $this->assertNotCount(0, $ret->files());
    }
}
