<?php declare(strict_types=1);

namespace Somnambulist\ProjectManager\Models;

use function strpos;

/**
 * Class Template
 *
 * @package    Somnambulist\ProjectManager\Models
 * @subpackage Somnambulist\ProjectManager\Models\Template
 */
final class Template
{

    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $type;

    /**
     * @var string|null
     */
    private $source;

    /**
     * Constructor
     *
     * @param string      $name
     * @param string      $type
     * @param string|null $source
     */
    public function __construct(string $name, string $type, string $source = null)
    {
        $this->name   = $name;
        $this->type   = $type;
        $this->source = $source;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function type(): string
    {
        return $this->type;
    }

    public function source(): ?string
    {
        return $this->source;
    }

    public function hasResource(): bool
    {
        return null !== $this->source;
    }

    public function isConfig(): bool
    {
        return $this->hasResource() && (!$this->isComposerResource() && !$this->isGitResource());
    }

    public function isComposerResource(): bool
    {
        return $this->hasResource() && 0 === strpos($this->source, 'composer:');
    }

    public function isGitResource(): bool
    {
        return $this->hasResource() && 0 === strpos($this->source, 'git:');
    }
}
