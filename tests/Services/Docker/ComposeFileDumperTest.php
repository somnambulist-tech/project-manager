<?php declare(strict_types=1);

namespace Somnambulist\ProjectManager\Tests\Services\Docker;

use Somnambulist\ProjectManager\Services\Docker\ComposeFileDumper;
use PHPUnit\Framework\TestCase;
use Somnambulist\ProjectManager\Services\Docker\ComposeFileLoader;
use function file_get_contents;

/**
 * Class ComposeFileDumperTest
 *
 * @package    Somnambulist\ProjectManager\Tests\Services\Docker
 * @subpackage Somnambulist\ProjectManager\Tests\Services\Docker\ComposeFileDumperTest
 */
class ComposeFileDumperTest extends TestCase
{

    public function testStore()
    {
        $loader = new ComposeFileLoader();
        $dc = $loader->load(__DIR__ . '/../../Stubs/config/docker/docker-compose.yml');

        $dumper = new ComposeFileDumper();
        $dumper->store($dc, $f = __DIR__ . '/../../../var/docker-compose.yml');

        $this->assertFileExists($f);
        $this->assertEquals(file_get_contents(__DIR__ . '/../../Stubs/config/docker/generated_file.yml'), file_get_contents($f));
    }
}
