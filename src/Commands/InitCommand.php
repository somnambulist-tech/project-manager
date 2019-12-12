<?php declare(strict_types=1);

namespace Somnambulist\ProjectManager\Commands;

use Somnambulist\ProjectManager\Commands\Behaviours\UseEnvironmentTemplate;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use function file_exists;
use function file_put_contents;
use function mkdir;
use const DIRECTORY_SEPARATOR;

/**
 * Class InitCommand
 *
 * @package Somnambulist\ProjectManager\Commands
 * @subpackage Somnambulist\ProjectManager\Commands\InitCommand
 */
class InitCommand extends BaseCommand
{

    use UseEnvironmentTemplate;

    protected function configure()
    {
        $this
            ->setName('init')
            ->setDescription('Initialise the Project Manager config folder')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->setupConsoleHelper($input, $output);

        $dir = $_SERVER['SOMNAMBULIST_PROJECTS_CONFIG_DIR'];

        if (!file_exists($dir)) {
            mkdir($dir, 0775, true);

            file_put_contents($dir . DIRECTORY_SEPARATOR . 'project_manager.yaml', $this->config());
            file_put_contents($dir . DIRECTORY_SEPARATOR . '.env', $this->environmentTemplate());

            $this->tools()->success('Configuration created at <comment>%s</comment>', $dir);
        } else {
            $this->tools()->warning('Configuration at <comment>%s</comment> already exists', $dir);
        }

        return 0;
    }

    private function config()
    {
        return <<<CFG
somnambulist:
    cache_dir: '\${SOMNAMBULIST_PROJECTS_CONFIG_DIR}/_cache'

    templates:
        library: ~
        web: ~
        service: 'somnambulist/symfony-micro-service'
        data: 'somnambulist/data-service'

CFG;
    }
}
