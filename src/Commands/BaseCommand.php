<?php declare(strict_types=1);

namespace Somnambulist\ProjectManager\Commands;

use Somnambulist\ProjectManager\Commands\Behaviours\IsRunningInDebugMode;
use Somnambulist\ProjectManager\Commands\Behaviours\UseConsoleHelper;
use Symfony\Component\Console\Command\Command;

/**
 * Class BaseCommand
 *
 * @package Somnambulist\ProjectManager\Commands
 * @subpackage Somnambulist\ProjectManager\Commands\BaseCommand
 */
abstract class BaseCommand extends Command
{

    use IsRunningInDebugMode;
    use UseConsoleHelper;

}
