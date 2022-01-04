<?php declare(strict_types=1);

namespace Somnambulist\ProjectManager\Commands\Config;

/**
 * Class CommandOptionResult
 *
 * @package    Somnambulist\ProjectManager\Commands\Config
 * @subpackage Somnambulist\ProjectManager\Commands\Config\CommandOptionResult
 */
final class CommandOptionResult
{
    public function __construct(
        private int $result,
        private string $success,
        private string $error,
        private string $info
    ) {
    }

    public static function ok(string $message = ''): CommandOptionResult
    {
        return new self(0, $message, '', '');
    }

    public static function error(string $message = ''): CommandOptionResult
    {
        return new self(1, '', $message, '');
    }

    public function success(): bool
    {
        return 0 === $this->result;
    }

    public function failed(): bool
    {
        return !$this->success();
    }

    public function getResult(): int
    {
        return $this->result;
    }

    public function getSuccessMessage(): string
    {
        return $this->success;
    }

    public function getErrorMessage(): string
    {
        return $this->error;
    }

    public function getInfoMessage(): string
    {
        return $this->info;
    }
}
