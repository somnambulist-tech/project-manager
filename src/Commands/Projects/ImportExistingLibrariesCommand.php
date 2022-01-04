<?php declare(strict_types=1);

namespace Somnambulist\ProjectManager\Commands\Projects;

use Somnambulist\ProjectManager\Commands\AbstractCommand;
use Somnambulist\ProjectManager\Commands\Behaviours\CanUpdateProjectConfiguration;
use Somnambulist\ProjectManager\Commands\Behaviours\GetCurrentActiveProject;
use Somnambulist\ProjectManager\Commands\Behaviours\ProjectConfigAwareCommand;
use Somnambulist\ProjectManager\Contracts\ProjectConfigAwareInterface;
use Somnambulist\ProjectManager\Models\Docker\Components\ComposeService;
use Somnambulist\ProjectManager\Models\Library;
use Somnambulist\ProjectManager\Models\Project;
use Somnambulist\ProjectManager\Models\Service;
use Somnambulist\ProjectManager\Services\Docker\ComposeFileLoader;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use function basename;
use function file_exists;
use function glob;
use function reset;
use function str_replace;
use const DIRECTORY_SEPARATOR;
use const GLOB_ONLYDIR;

/**
 * Class ImportExistingLibrariesCommand
 *
 * @package    Somnambulist\ProjectManager\Commands\Projects
 * @subpackage Somnambulist\ProjectManager\Commands\Projects\ImportExistingLibrariesCommand
 */
class ImportExistingLibrariesCommand extends AbstractCommand implements ProjectConfigAwareInterface
{

    use GetCurrentActiveProject;
    use ProjectConfigAwareCommand;
    use CanUpdateProjectConfiguration;

    protected function configure(): void
    {
        $this
            ->setName('project:import')
            ->setAliases(['import'])
            ->setDescription('Import and categorise any existing libraries / services in the project directory')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->setupConsoleHelper($input, $output);

        $project = $this->getActiveProject();

        $this->tools()->info('working on <info>%s</info>', $project->name());

        if ($project->services()->count() !== 0 || $project->libraries()->count() !== 0) {
            $this->tools()->warning('the project already has libraries and/or services defined');
            $this->tools()->info('skipping auto-import');
            $this->tools()->newline();

            return 1;
        }

        $cwd = $project->workingPath();

        $this->tools()->warning('scanning for existing libraries / services in <info>%s</info>', $cwd);

        $entries = glob($cwd . DIRECTORY_SEPARATOR . '*', GLOB_ONLYDIR);
        $step    = 0;

        foreach ($entries as $entry) {
            if (file_exists($entry . DIRECTORY_SEPARATOR . 'Vagrantfile')) {
                // ignore vagrant vms
                continue;
            }
            if (file_exists($entry . DIRECTORY_SEPARATOR . 'docker-compose.yml')) {
                $this->importService($project, $entry, ++$step);
                continue;
            }
            if (file_exists($entry . DIRECTORY_SEPARATOR . 'composer.json')) {
                $this->importLibrary($project, $entry, ++$step);
            }
        }

        $this->updateProjectConfig($project, ++$step);

        $this->tools()->success('existing projects successfully imported');
        $this->tools()->info('you should customise the config data if you need to e.g.: names, repos, containers');
        $this->tools()->newline();

        return 0;
    }

    private function importService(Project $project, string $cwd, int $step): void
    {
        $name      = trim(str_replace(['-service', '-app', '-manager'], '', basename($cwd)));
        $dirname   = basename($cwd);
        $repo      = $this->getRemoteRepository($cwd);
        $container = $this->getAppContainerName($cwd);

        if ($container) {
            $this->tools()->step($step, 'adding <info>%s</info> for <info>%s</info> to services', $container, $name);

            $project->services()->add(new Service($name, $dirname, $repo, null, $container));
        } else {
            $this->tools()->error('failed to find a suitable container name for <info>%s</info>', $name);
        }
    }

    private function importLibrary(Project $project, string $cwd, int $step): void
    {
        $name = basename($cwd);
        $repo = $this->getRemoteRepository($cwd);

        $this->tools()->step($step, 'adding <info>%s</info> to libraries', $name);

        $project->libraries()->add(new Library($name, $name, $repo));
    }

    private function getRemoteRepository(string $cwd): ?string
    {
        $repos = $this->tools()->git()->getRemotes($cwd);

        return reset($repos) ?: null;
    }

    private function getAppContainerName(string $cwd): ?string
    {
        $config = (new ComposeFileLoader())->load($cwd . DIRECTORY_SEPARATOR . 'docker-compose.yml');

        foreach ($config->services() as $key => $service) {
            /** @var ComposeService $service */
            if (str_contains($key, '-app')) {
                return $key;
            }

            if (
                $service->labels()->has('traefik.enable') && $service->labels()->get('traefik.enable')
                &&
                $service->labels()->matching('/^traefik.http.routers/')-> count() > 0
            ) {
                return $key;
            }
        }

        return null;
    }
}
