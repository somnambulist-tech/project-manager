<?php declare(strict_types=1);

namespace Somnambulist\ProjectManager\Commands;

use Somnambulist\ProjectManager\Commands\Behaviours\ProjectConfigAwareCommand;
use Somnambulist\ProjectManager\Contracts\ProjectConfigAwareInterface;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class EnvParametersCommand
 *
 * @package Somnambulist\ProjectManager\Commands
 * @subpackage Somnambulist\ProjectManager\Commands\EnvParametersCommand
 */
class EnvParametersCommand extends AbstractCommand implements ProjectConfigAwareInterface
{

    use ProjectConfigAwareCommand;

    protected function configure()
    {
        $this
            ->setName('params')
            ->setDescription('Display all available environment substitutions')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $params = $this
            ->config
            ->parameters()
            ->map(function ($value, $key) {
                return [$key, $value];
            })
            ->values()
            ->toArray()
        ;

        $table = new Table($output);
        $table
            ->setHeaderTitle('Available Environment Variables')
            ->setHeaders(['Parameter', 'Current Value'])
            ->setRows($params)
        ;

        $table->render();

        return 0;
    }
}
