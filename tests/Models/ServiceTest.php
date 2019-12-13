<?php declare(strict_types=1);

namespace Somnambulist\ProjectManager\Tests\Models;

use PHPUnit\Framework\TestCase;
use Somnambulist\Collection\FrozenCollection;
use Somnambulist\ProjectManager\Models\Service;

/**
 * Class ServiceTest
 *
 * @package    Somnambulist\ProjectManager\Tests\Models
 * @subpackage Somnambulist\ProjectManager\Tests\Models\ServiceTest
 *
 * @group models
 * @group models-service
 */
class ServiceTest extends TestCase
{

    public function testCreate()
    {
        $ent = new Service('test', 'foo', 'bar', 'app', ['foo', 'bar']);

        $this->assertEquals('test', $ent->name());
        $this->assertEquals('foo', $ent->directoryName());
        $this->assertEquals('bar', $ent->repository());
        $this->assertEquals('app', $ent->appContainer());
        $this->assertInstanceOf(FrozenCollection::class, $ent->dependencies());
        $this->assertEquals(['foo', 'bar'], $ent->dependencies()->toArray());
    }

    public function testHasDependencies()
    {
        $ent = new Service('test', 'foo', 'bar', 'app', ['foo', 'bar']);

        $this->assertTrue($ent->hasDependencies());

        $ent = new Service('test', 'foo', 'bar', 'app');

        $this->assertFalse($ent->hasDependencies());
    }

    public function testInstallPath()
    {
        $dir = $_SERVER['PROJECT_SERVICES_DIR'] = dirname(__DIR__, 3);

        $ent = new Service('test', 'project-manager', 'bar', 'app');

        $this->assertEquals($dir . '/project-manager', $ent->installPath());

        $this->assertTrue($ent->isInstalled());
    }
}
