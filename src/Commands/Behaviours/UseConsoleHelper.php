<?php declare(strict_types=1);

namespace Somnambulist\ProjectManager\Commands\Behaviours;

use RuntimeException;
use Somnambulist\ProjectManager\Services\Console\ConsoleHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Trait UseConsoleHelper
 *
 * @package    Somnambulist\ProjectManager\Commands\Behaviours
 * @subpackage Somnambulist\ProjectManager\Commands\Behaviours\UseConsoleHelper
 */
trait UseConsoleHelper
{
    protected ?ConsoleHelper $consoleHelper = null;

    protected function setupConsoleHelper(InputInterface $input, OutputInterface $output): void
    {
        $this->consoleHelper = new ConsoleHelper($input, $output);
    }

    protected function tools(): ConsoleHelper
    {
        if (!$this->consoleHelper) {
            throw new RuntimeException('ConsoleHelper has not been setup, call ->setupConsoleHelper first');
        }

        return $this->consoleHelper;
    }
}
