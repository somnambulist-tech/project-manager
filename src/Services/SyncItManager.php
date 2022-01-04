<?php declare(strict_types=1);

namespace Somnambulist\ProjectManager\Services;

use IlluminateAgnostic\Str\Support\Str;
use Somnambulist\ProjectManager\Models\Service;
use Somnambulist\ProjectManager\Services\Console\ConsoleHelper;
use function shell_exec;
use function stripos;
use function trim;

/**
 * Class SyncItManager
 *
 * @package    Somnambulist\ProjectManager\Services
 * @subpackage Somnambulist\ProjectManager\Services\SyncItManager
 */
class SyncItManager
{
    private bool $available;
    private ConsoleHelper $helper;

    /**
     * An array of ENV names that must not be passed through to other commands
     */
    private array $toRemove = [
        'APP_ENV'              => false,
        'COMPOSE_PROJECT_NAME' => false,
        'PROJECT_DIR'          => false,
        'SYMFONY_DOTENV_VARS'  => false,
    ];

    public function __construct()
    {
        $this->available = $this->isSyncItInstalled();
    }

    public function bindConsoleHelper(ConsoleHelper $helper): void
    {
        $this->helper = $helper;
    }

    private function isSyncItInstalled(): bool
    {
        if (null !== $ret = shell_exec('which syncit')) {
            return Str::endsWith(trim($ret), '/syncit');
        }

        return false;
    }

    private function execute(Service $service, string $command): bool
    {
        if (!$this->available || !$service->isInstalled()) {
            return false;
        }

        if ($this->helper->isDebugEnabled()) {
            $command .= ' -vvv';
        }

        return $this->helper->execute($command, $service->installPath(), $this->toRemove);
    }

    public function isRunning(Service $service): ?string
    {
        if (!$this->available || !$service->isInstalled()) {
            return null;
        }

        if (null === $output = $this->helper->run('syncit status', $service->installPath(), $this->toRemove)) {
            return null;
        }

        return !is_null($output) && false !== stripos($output, 'connected') ? 'running' : 'stopped';
    }

    public function start(Service $service): bool
    {
        return $this->execute($service, 'syncit start all');
    }

    public function stop(Service $service): bool
    {
        return $this->execute($service, 'syncit stop all');
    }
}
