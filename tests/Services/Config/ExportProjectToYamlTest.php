<?php declare(strict_types=1);

namespace Somnambulist\ProjectManager\Tests\Services\Config;

use Somnambulist\ProjectManager\Services\Config\ConfigLocator;
use Somnambulist\ProjectManager\Services\Config\ConfigParser;
use Somnambulist\ProjectManager\Services\Config\ExportProjectToYaml;
use PHPUnit\Framework\TestCase;
use function dirname;
use function file_exists;
use function realpath;

/**
 * Class ExportProjectToYamlTest
 *
 * @package    Somnambulist\ProjectManager\Tests\Services\Config
 * @subpackage Somnambulist\ProjectManager\Tests\Services\Config\ExportProjectToYamlTest
 *
 * @group services
 * @group services-config
 * @group services-config-export-project
 * @group cur
 */
class ExportProjectToYamlTest extends TestCase
{

    protected function setUp(): void
    {
        $this->cleanup();
    }

    protected function tearDown(): void
    {
        $this->cleanup();
    }

    public function testExport()
    {
        $tmp = $_SERVER[ConfigLocator::ENV_NAME] = realpath(dirname(__DIR__) . '/../Stubs/config');

        $test = $tmp . '/somnambulist/expected_yaml.yaml';
        $file = $tmp . '/somnambulist/generated_yaml.yaml';

        $parser = new ConfigParser();
        $config = $parser->parse((new ConfigLocator())->locate());

        $project = $config->projects()->get('somnambulist');

        (new ExportProjectToYaml())->export($project, $file);

        $this->assertFileEquals($test, $file);
    }

    private function cleanup(): void
    {
        $tmp = realpath(dirname(__DIR__) . '/../Stubs/config');

        $file = $tmp . '/somnambulist/generated_yaml.yaml';

        if (file_exists($file)) {
            unlink($file);
        }
    }
}
