<?php declare(strict_types=1);

namespace Somnambulist\ProjectManager\Commands;

use Somnambulist\ProjectManager\Commands\Behaviours\GetCurrentActiveProject;
use Somnambulist\ProjectManager\Commands\Behaviours\ProjectConfigAwareCommand;
use Somnambulist\ProjectManager\Contracts\InstallableResource;
use Somnambulist\ProjectManager\Contracts\ProjectConfigAwareInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use function is_null;

/**
 * Class UpdateLibraryCommand
 *
 * @package    Somnambulist\ProjectManager\Commands
 * @subpackage Somnambulist\ProjectManager\Commands\UpdateLibraryCommand
 */
class UpdateLibraryCommand extends AbstractCommand implements ProjectConfigAwareInterface
{

    use GetCurrentActiveProject;
    use ProjectConfigAwareCommand;

    protected function configure()
    {
        $this
            ->setName('update')
            ->setDescription('Updates all local libraries/services from the configured remotes')
            ->addArgument('branch', InputArgument::OPTIONAL, 'The name of the mainline branch to switch to', 'develop')
            ->addOption('services', 's', InputOption::VALUE_NONE, 'Update only services')
            ->addOption('libraries', 'l', InputOption::VALUE_NONE, 'Update only libraries')
            ->setHelp(<<<HLP

Attempts to switch all libraries (services or libraries) to the latest branch
specified. The branch should exist in all the repositories on the remotes and
remotes must be configured. While any branch can be used, in practice this
will be either <info>master</info> or <info>develop</info>.

Before performing any actions, any outstanding changes are stashed along with
any untracked files.

When switching branches, the tracking will be set to the origin branch.

If any step produces an error, no further actions will be performed on that
library.

<comment>Note:</comment> only installed libraries will be processed.

HLP)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->setupConsoleHelper($input, $output);

        $project = $this->getActiveProject();

        $this->tools()->info('active project is <info>%s</info>', $project->name());

        $branch    = $input->getArgument('branch');
        $services  = $input->getOption('services');
        $libraries = $input->getOption('libraries');
        $all       = (false === $services && false === $libraries);
        $libs      = null;

        $this->tools()->info('updating libraries to latest <info>%s</info>', $branch);

        if ('y' !== $this->tools()->ask('Are you sure you wish to update all matching libraries? (y/n) ', false)) {
            return 0;
        }

        if ($services) {
            $libs = $project->services()->list();
        }
        if ($libraries) {
            $libs = $project->libraries()->list();
        }
        if ($all) {
            $libs = $project->libraries()->list()->merge($project->services()->list());
        }

        if (is_null($libs)) {
            return 1;
        }

        foreach ($libs as $lib) {
            /** @var InstallableResource $lib */
            if (!$lib->isInstalled()) {
                $this->tools()->info('<info>%s</info> is not installed, skipping', $lib->name());
                $this->tools()->newline();
                continue;
            }

            $this->tools()->info('<step> %s </step> stashing any outstanding commits and un-tracked files', $lib->name());
            if (!$this->tools()->git()->stash($lib->installPath())) {
                $this->tools()->error('<step> %s </step> failed to stash changes: aborting this update', $lib->name());
                $this->tools()->newline();
                continue;
            }

            $this->tools()->info('<step> %s </step> switching to <info>%s</info>', $lib->name(), $branch);
            if (!$this->tools()->git()->checkout($lib->installPath(), 'origin', $branch)) {
                $this->tools()->error('<step> %s </step> failed to checkout branch; does it exist?', $lib->name());
                $this->tools()->newline();
                continue;
            }

            $this->tools()->info('<step> %s </step> pulling latest changes from origin', $lib->name());
            if (!$this->tools()->git()->pull($lib->installPath(), 'origin', $branch)) {
                $this->tools()->error('<step> %s </step> failed to pull remote branch', $lib->name());
                $this->tools()->newline();
                continue;
            }
        }

        return 0;
    }
}