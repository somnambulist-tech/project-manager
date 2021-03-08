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

    /**
     * @var array
     */
    private $test;

    /**
     * @var string
     */
    private $interval;

    /**
     * @var string
     */
    private $timeout;

    /**
     * @var int
     */
    private $retries;

    /**
     * @var string
     */
    private $startPeriod;

    public function __construct(array $test, ?string $interval, ?string $timeout, ?int $retries, ?string $startPeriod)
    {
        $this->test        = $test;
        $this->interval    = $interval;
        $this->timeout     = $timeout;
        $this->retries     = $retries;
        $this->startPeriod = $startPeriod;
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
