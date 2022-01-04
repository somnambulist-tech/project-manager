<?php declare(strict_types=1);

namespace Somnambulist\ProjectManager\Tests\Services\Config;

use Somnambulist\Components\Collection\FrozenCollection;
use Somnambulist\ProjectManager\Models\Config;
use PHPUnit\Framework\TestCase;
use Somnambulist\ProjectManager\Models\Project;
use Somnambulist\ProjectManager\Services\Config\ConfigLocator;
use Somnambulist\ProjectManager\Services\Config\ConfigParser;
use function dirname;
use function realpath;

/**
 * Class ConfigTest
 *
 * @package    Somnambulist\ProjectManager\Tests\Services\Config
 * @subpackage Somnambulist\ProjectManager\Tests\Services\Config\ConfigTest
 *
 * @group models
 * @group models-config
 */
class ConfigTest extends TestCase
{

    /**
     * @var Config
     */
    private $config;

    protected function setUp(): void
    {
        $_SERVER['SOMNAMBULIST_ACTIVE_PROJECT'] = 'somnambulist';
        $_SERVER[ConfigLocator::ENV_NAME] = realpath(dirname(__DIR__) . '/Stubs/config');

        $parser = new ConfigParser();
        $this->config = $parser->parse((new ConfigLocator())->locate());
    }

    public function testCreate()
    {
        $config = $this->config;

        $this->assertEquals(dirname(__DIR__, 2) . '/tests/Stubs/config', $config->home());
        $this->assertInstanceOf(FrozenCollection::class, $config->config());
        $this->assertInstanceOf(FrozenCollection::class, $config->parameters());

        $this->assertCount(2, $config->projects());
        $this->assertNull($config->config()->get('templates'));

        $project = $config->projects()->get('example');

        $this->assertInstanceOf(Project::class, $project);
        $this->assertEquals('example', $project->name());
        $this->assertCount(0, $project->services());
        $this->assertCount(1, $project->libraries());

        $templates = $config->templates();

        $this->assertCount(6, $templates);
    }

    public function testAvailableTemplates()
    {
        $templates = $this->config->availableTemplates('library');

        $this->assertEquals(['api-client', 'bundle', 'client', 'library', 'package'], $templates);
    }
}
