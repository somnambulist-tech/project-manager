<?php declare(strict_types=1);

namespace Somnambulist\ProjectManager\Commands\Projects;

use Somnambulist\ProjectManager\Commands\AbstractCommand;
use Somnambulist\ProjectManager\Commands\Behaviours\GetProjectFromInput;
use Somnambulist\ProjectManager\Commands\Behaviours\ProjectConfigAwareCommand;
use Somnambulist\ProjectManager\Contracts\ProjectConfigAwareInterface;
use Somnambulist\ProjectManager\Models\Config;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class PushProjectConfigCommand
 *
 * @package    Somnambulist\ProjectManager\Commands\Projects
 * @subpackage Somnambulist\ProjectManager\Commands\Projects\PushProjectConfigCommand
 */
class PushProjectConfigCommand extends AbstractCommand implements ProjectConfigAwareInterface
{

    use GetProjectFromInput;
    use ProjectConfigAwareCommand;

    protected function configure()
    {
        $this
            ->setName('project:push')
            ->setDescription('Commit any configuration changes and push to the git repository')
            ->addArgument('project', InputArgument::OPTIONAL, 'The project name')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->setupConsoleHelper($input, $output);

        $project = $this->getProjectFrom($input);

        $this->tools()->warning('storing all outstanding configuration changes', $project);

        $cwd = $project->configPath();

        $ok = $this->tools()->execute('git add -A', $cwd);
        $ok = $ok && $this->tools()->execute('git commit -m \'updating configuration files\'', $cwd);
        $ok = $ok && $this->tools()->execute('git push origin master', $cwd);

        if ($ok) {
            $this->tools()->success('project changes successfully sync\'d');
            $this->tools()->newline();

            return 0;
        } else {
            $this->tools()->error('failed to update the git repository; did you have any changes? Re-run with -vvv to check');
            $this->tools()->info('You may not have write access to the repository, or are out of sync');
            $this->tools()->newline();

            return 1;
        }
    }
}
