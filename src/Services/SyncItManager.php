<?php declare(strict_types=1);

namespace Somnambulist\ProjectManager\Services;

use IlluminateAgnostic\Str\Support\Str;
use Somnambulist\ProjectManager\Models\Service;
use Somnambulist\ProjectManager\Services\Console\ConsoleHelper;
use function shell_exec;
use function trim;

/**
 * Class SyncItManager
 *
 * @package    Somnambulist\ProjectManager\Services
 * @subpackage Somnambulist\ProjectManager\Services\SyncItManager
 */
class SyncItManager
{

    /**
     * @var bool
     */
    private $available = false;

    /**
     * @var ConsoleHelper
     */
    private $helper;

    public function __construct()
    {
        $this->available = $this->isSyncItInstalled();
    }

    public function bindConsoleHelper(ConsoleHelper $helper): void
    {
        $this->helper = $helper;
    }

    private function isSyncItInstalled()
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

        return $this->helper->execute($command, $service->installPath());
    }

    public function start(Service $service)
    {
        return $this->execute($service, 'syncit start all');
    }

    public function stop(Service $service)
    {
        return $this->execute($service, 'syncit stop all');
    }
}
