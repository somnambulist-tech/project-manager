<?php declare(strict_types=1);

namespace Somnambulist\ProjectManager\Models;

/**
 * Class Template
 *
 * @package    Somnambulist\ProjectManager\Models
 * @subpackage Somnambulist\ProjectManager\Models\Template
 */
final class Template
{
    public function __construct(private string $name, private string $type, private ?string $source = null)
    {
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
        return $this->hasResource() && str_starts_with($this->source, 'composer:');
    }

    public function isGitResource(): bool
    {
        return $this->hasResource() && str_starts_with($this->source, 'git:');
    }
}
