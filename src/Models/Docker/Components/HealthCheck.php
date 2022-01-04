<?php declare(strict_types=1);

namespace Somnambulist\ProjectManager\Models\Docker\Components;

/**
 * Class HealthCheck
 *
 * @package    Somnambulist\ProjectManager\Models\Docker\Components
 * @subpackage Somnambulist\ProjectManager\Models\Docker\Components\HealthCheck
 */
class HealthCheck
{
    public function __construct(
        private array $test,
        private ?string $interval,
        private ?string $timeout,
        private ?int $retries,
        private ?string $startPeriod
    ) {
    }

    public static function from(array $data): ?HealthCheck
    {
        if (empty($data)) {
            return null;
        }

        return new HealthCheck(
            $data['test'],
            $data['interval'] ?? null,
            $data['timeout'] ?? null,
            $data['retries'] ?? null,
            $data['start_period'] ?? null,
        );
    }

    public function test(): array
    {
        return $this->test;
    }

    public function interval(): ?string
    {
        return $this->interval;
    }

    public function timeout(): ?string
    {
        return $this->timeout;
    }

    public function retries(): ?int
    {
        return $this->retries;
    }

    public function startPeriod(): ?string
    {
        return $this->startPeriod;
    }

    public function exportForYaml(): array
    {
        $ret = ['test' => $this->test];

        if ($this->interval) {
            $ret['interval'] = $this->interval;
        }
        if ($this->timeout) {
            $ret['timeout'] = $this->timeout;
        }
        if ($this->retries) {
            $ret['retries'] = $this->retries;
        }
        if ($this->startPeriod) {
            $ret['start_period'] = $this->startPeriod;
        }

        return $ret;
    }
}
