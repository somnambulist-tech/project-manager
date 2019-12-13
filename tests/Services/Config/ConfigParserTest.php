<?php declare(strict_types=1);

namespace Somnambulist\ProjectManager\Tests\Services\Config;

use Somnambulist\ProjectManager\Models\Project;
use Somnambulist\ProjectManager\Models\Template;
use Somnambulist\ProjectManager\Services\Config\ConfigLocator;
use Somnambulist\ProjectManager\Services\Config\ConfigParser;
use PHPUnit\Framework\TestCase;
use function dirname;
use function realpath;

/**
 * Class ConfigParserTest
 *
 * @package    Somnambulist\ProjectManager\Tests\Services\Config
 * @subpackage Somnambulist\ProjectManager\Tests\Services\Config\ConfigParserTest
 *
 * @group      services
 * @group      services-config
 * @group      services-config-parser
 */
class ConfigParserTest extends TestCase
{

    public function testParse()
    {
        $tmp = $_SERVER[ConfigLocator::ENV_NAME] = realpath(dirname(__DIR__) . '/../Stubs/config');

        $parser = new ConfigParser();
        $config = $parser->parse((new ConfigLocator())->locate());

        $this->assertCount(2, $config->projects());
        $this->assertEquals($tmp . '/_cache', $config->config()->get('cache_dir'));
        $this->assertNull($config->config()->get('templates'));

        $project = $config->projects()->get('example');

        $this->assertInstanceOf(Project::class, $project);
        $this->assertEquals('example', $project->name());
        $this->assertCount(0, $project->services());
        $this->assertCount(1, $project->libraries());

        $templates = $config->templates();

        $this->assertCount(6, $templates);

        $template = $templates->get('data');

        $this->assertInstanceOf(Template::class, $template);
        $this->assertEquals('data', $template->name());
        $this->assertEquals('service', $template->type());
        $this->assertEquals('composer:somnambulist/data-service', $template->source());
        $this->assertTrue($template->isComposerResource());
    }
}
