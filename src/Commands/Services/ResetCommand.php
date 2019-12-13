<?php declare(strict_types=1);

namespace Somnambulist\ProjectManager\Commands\Services;

use Somnambulist\ProjectManager\Commands\AbstractCommand;
use Somnambulist\ProjectManager\Commands\Behaviours\DockerAwareCommand;
use Somnambulist\ProjectManager\Contracts\DockerAwareInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class ResetCommand
 *
 * @package Somnambulist\ProjectManager\Commands\Services
 * @subpackage Somnambulist\ProjectManager\Commands\Services\ResetCommand
 */
class ResetCommand extends AbstractCommand implements DockerAwareInterface
{

    use DockerAwareCommand;

    protected function configure()
    {
        $this
            ->setName('services:reset')
            ->setDescription('Flushes the docker containers, volumes and cache images')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->setupConsoleHelper($input, $output);

        if ($this->docker->reset()) {
            $this->tools()->success('Docker environment fully purged of all data');

            return 0;
        } else {
            $this->tools()->error('there was a problem running <info>docker system prune</info>');

            return 1;
        }
    }
}
