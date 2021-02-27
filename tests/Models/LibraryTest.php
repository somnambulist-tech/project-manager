<?php declare(strict_types=1);

namespace Somnambulist\ProjectManager\Tests\Models;

use Somnambulist\ProjectManager\Models\Library;
use PHPUnit\Framework\TestCase;

/**
 * Class LibraryTest
 *
 * @package    Somnambulist\ProjectManager\Tests\Models
 * @subpackage Somnambulist\ProjectManager\Tests\Models\LibraryTest
 *
 * @group models
 * @group models-library
 */
class LibraryTest extends TestCase
{

    public function testCreate()
    {
        $ent = new Library('test', 'foo', 'bar', 'baz');

        $this->assertEquals('test', $ent->name());
        $this->assertEquals('foo', $ent->directoryName());
        $this->assertEquals('bar', $ent->repository());
        $this->assertEquals('baz', $ent->branch());
    }

    public function testInstallPath()
    {
        $dir = $_SERVER['PROJECT_LIBRARIES_DIR'] = dirname(__DIR__, 3);

        $ent = new Library('test', 'project-manager', 'bar');

        $this->assertEquals($dir . '/project-manager', $ent->installPath());

        $this->assertTrue($ent->isInstalled());
    }
}
