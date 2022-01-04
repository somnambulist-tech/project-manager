<?php declare(strict_types=1);

namespace Somnambulist\ProjectManager\Commands\Projects;

use Exception;
use Somnambulist\ProjectManager\Commands\AbstractCommand;
use Somnambulist\ProjectManager\Commands\Behaviours\DockerAwareCommand;
use Somnambulist\ProjectManager\Commands\Behaviours\GetCurrentActiveProject;
use Somnambulist\ProjectManager\Commands\Behaviours\GetProjectFromInput;
use Somnambulist\ProjectManager\Commands\Behaviours\ProjectConfigAwareCommand;
use Somnambulist\ProjectManager\Commands\Behaviours\UseEnvironmentTemplate;
use Somnambulist\ProjectManager\Contracts\DockerAwareInterface;
use Somnambulist\ProjectManager\Contracts\ProjectConfigAwareInterface;
use Somnambulist\ProjectManager\Models\Project;
use Somnambulist\ProjectManager\Models\Service;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use function array_filter;
use function file_put_contents;
use function implode;
use function strtolower;
use const DIRECTORY_SEPARATOR;

/**
 * Class SwitchProjectCommand
 *
 * @package    Somnambulist\ProjectManager\Commands\Projects
 * @subpackage Somnambulist\ProjectManager\Commands\Projects\SwitchProjectCommand
 */
class SwitchProjectCommand extends AbstractCommand implements DockerAwareInterface, ProjectConfigAwareInterface
{

    use UseEnvironmentTemplate;
    use DockerAwareCommand;
    use GetCurrentActiveProject;
    use GetProjectFromInput;
    use ProjectConfigAwareCommand;

    protected function configure(): void
    {
        $this
            ->setName('use')
            ->setAliases(['switch',])
            ->setDescription('Switch the current project to the one specified')
            ->addArgument('project', InputArgument::OPTIONAL, 'The project name')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->setupConsoleHelper($input, $output);

        $this->checkCurrentProjectIsRunning();

        $project = $this->getProjectFrom($input);

        $this->tools()->warning('switching active project to <info>%s</info>', $project->name());

        $this->switch($project);

        $this->tools()->success('active project is now <info>%s</info>', $project->name());
        $this->tools()->newline();

        return 0;
    }

    private function switch(Project $project)
    {
        $file = $this->config->home() . DIRECTORY_SEPARATOR . '.env';

        file_put_contents(
            $file, $this->environmentTemplate(
                $project->name(),
                $project->workingPath(),
                $this->makePath($project->workingPath(), $project->librariesName()),
                $this->makePath($project->workingPath(), $project->servicesName())
            )
        );
    }

    private function makePath(?string ...$args): string
    {
        return implode(DIRECTORY_SEPARATOR, array_filter($args));
    }

    private function checkCurrentProjectIsRunning(): void
    {
        try {
            $project = $this->getActiveProject();
            $cnt     = $project->services()->list()
                ->each(fn(Service $s) => $this->docker->resolve($s))
                ->filter(fn(Service $s) => $s->isRunning())
                ->count()
            ;

            if ($cnt > 0) {
                if ('y' == strtolower($this->tools()->ask('Project <info>%s</info> has running services. Should these be stopped? [y/n] ', false, $project->name()))) {
                    $this->tools()->info('stopping running services in <info>%s</info>', $project->name());
                    $this->tools()->run('spm stop all');
                }
            }
        } catch (Exception) {

        }
    }
}
