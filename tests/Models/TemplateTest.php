<?php declare(strict_types=1);

namespace Somnambulist\ProjectManager\Tests\Models;

use Somnambulist\ProjectManager\Models\Template;
use PHPUnit\Framework\TestCase;

/**
 * Class TemplateTest
 *
 * @package    Somnambulist\ProjectManager\Tests\Models
 * @subpackage Somnambulist\ProjectManager\Tests\Models\TemplateTest
 *
 * @group models
 * @group models-template
 */
class TemplateTest extends TestCase
{

    public function testCreate()
    {
        $ent = new Template('test', 'foo', 'bar');

        $this->assertEquals('test', $ent->name());
        $this->assertEquals('foo', $ent->type());
        $this->assertEquals('bar', $ent->source());
    }

    public function testRequiresSourceToHaveResource()
    {
        $ent = new Template('test', 'foo');

        $this->assertFalse($ent->hasResource());

        $ent = new Template('test', 'foo', 'bob');

        $this->assertTrue($ent->hasResource());
    }

    public function testIsGitResource()
    {
        $ent = new Template('test', 'foo', 'git:git@github.com:dave-redfern/cms-service.git');

        $this->assertTrue($ent->isGitResource());
    }

    public function testIsComposerResource()
    {
        $ent = new Template('test', 'foo', 'composer:somnambulist/data-service');

        $this->assertTrue($ent->isComposerResource());
    }
}
