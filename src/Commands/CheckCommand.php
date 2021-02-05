<?php declare(strict_types=1);

namespace Somnambulist\ProjectManager\Commands;

use Somnambulist\ProjectManager\Commands\Behaviours\ProjectConfigAwareCommand;
use Somnambulist\ProjectManager\Contracts\ProjectConfigAwareInterface;
use Somnambulist\ProjectManager\Models\Template;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use function file_exists;
use function file_get_contents;
use function filesize;
use function is_dir;
use function trim;
use const DIRECTORY_SEPARATOR;

/**
 * Class CheckCommand
 *
 * @package    Somnambulist\ProjectManager\Commands
 * @subpackage Somnambulist\ProjectManager\Commands\CheckCommand
 */
class CheckCommand extends AbstractCommand implements ProjectConfigAwareInterface
{

    use ProjectConfigAwareCommand;

    protected function configure()
    {
        $this
            ->setName('check')
            ->setDescription('Check spm configuration for issues to help with debugging')
            ->addOption('debug', 'd', InputOption::VALUE_NONE, 'Display debug information with check data')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->setupConsoleHelper($input, $output);

        $dir = $_SERVER['SOMNAMBULIST_PROJECTS_CONFIG_DIR'];
        $cfg = $dir . DIRECTORY_SEPARATOR . 'project_manager.yaml';
        $env = $dir . DIRECTORY_SEPARATOR . '.env';

        $this->tools()->info('spm config dir: <info>%s</info>', $dir);
        $this->tools()->info('spm config dir exists: <info>%s</info>', (is_dir($dir) ? 'yes' : 'no'));
        $this->tools()->info('spm config file: <info>%s</info>', $cfg);
        $this->tools()->info('spm config file initialised: <info>%s</info>', (0 !== filesize($cfg) ? 'yes' : 'no'));
        $this->tools()->info('spm config file exists: <info>%s</info>', (file_exists($cfg) ? 'yes' : 'no'));

        if (!file_exists($cfg) || 0 === filesize($cfg)) {
            $this->tools()->warning('config file has not been properly initialised, re-run <info>spm init</info>');
            $this->tools()->newline();

            return 0;
        }

        if ($input->getOption('debug') && file_exists($cfg)) {
            $this->tools()->info('spm config');
            $this->tools()->message(trim(file_get_contents($cfg)));
        }

        $this->tools()->newline();
        $this->tools()->info('spm environment file: <info>%s</info>', $env);
        $this->tools()->info('spm environment file exists: <info>%s</info>', (file_exists($env) ? 'yes' : 'no'));

        if ($input->getOption('debug') && file_exists($env)) {
            $this->tools()->info('spm environment');
            $this->tools()->message(trim(file_get_contents($env)));
        }

        $this->tools()->newline();
        $info = $this->config->active() ? sprintf('(includes templates for <info>%s</info>)', $this->config->active()) : '';

        $this->tools()->info('configured templates %s', $info);

        $table = new Table($output);
        $table->setHeaders(['Name', 'Type', 'Source']);

        $this->templates()->each(function (Template $template) use ($table) {
            $table->addRow([$template->name(), $template->type(), $template->source() ?: '~']);
        });

        $table->render();
        $this->tools()->newline();

        return 0;
    }

    private function templates()
    {
        $items = $this->config->templates()->list();

        if (null !== $project = $this->config->projects()->active()) {
            $items->merge($project->templates()->list());
        }

        $items->sort(function (Template $t1, Template $t2) {
            return $t1->name() <=> $t2->name();
        });

        return $items;
    }
}
