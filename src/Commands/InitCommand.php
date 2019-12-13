<?php declare(strict_types=1);

namespace Somnambulist\ProjectManager\Commands;

use Somnambulist\ProjectManager\Commands\Behaviours\UseEnvironmentTemplate;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use function file_exists;
use function file_get_contents;
use function file_put_contents;
use function mkdir;
use const DIRECTORY_SEPARATOR;

/**
 * Class InitCommand
 *
 * @package Somnambulist\ProjectManager\Commands
 * @subpackage Somnambulist\ProjectManager\Commands\InitCommand
 */
class InitCommand extends AbstractCommand
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

            $this->tools()->success('Configuration created at <info>%s</info>', $dir);
        } else {
            $this->tools()->warning('Configuration at <info>%s</info> already exists', $dir);
        }

        $this->tools()->newline();

        return 0;
    }

    private function config()
    {
        return file_get_contents(dirname(__DIR__, 2) . '/config/project_manager.yaml');
    }
}
