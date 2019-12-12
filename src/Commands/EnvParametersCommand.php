<?php declare(strict_types=1);

namespace Somnambulist\ProjectManager\Commands;

use Somnambulist\ProjectManager\Models\Config;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use function is_array;

/**
 * Class EnvParametersCommand
 *
 * @package Somnambulist\ProjectManager\Commands
 * @subpackage Somnambulist\ProjectManager\Commands\EnvParametersCommand
 */
class EnvParametersCommand extends AbstractCommand
{

    /**
     * @var Config
     */
    private $config;

    /**
     * Constructor
     *
     * @param Config $config
     */
    public function __construct(Config $config)
    {
        $this->config = $config;

        parent::__construct();
    }

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
