<?php declare(strict_types=1);

namespace Somnambulist\ProjectManager\Commands;

use Somnambulist\ProjectManager\Commands\Behaviours\GetCurrentActiveProject;
use Somnambulist\ProjectManager\Commands\Behaviours\ProjectConfigAwareCommand;
use Somnambulist\ProjectManager\Contracts\ProjectConfigAwareInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use function str_replace;

/**
 * Class OpenLibraryCommand
 *
 * @package    Somnambulist\ProjectManager\Commands
 * @subpackage Somnambulist\ProjectManager\Commands\OpenLibraryCommand
 */
class OpenLibraryCommand extends AbstractCommand implements ProjectConfigAwareInterface
{

    use GetCurrentActiveProject;
    use ProjectConfigAwareCommand;

    protected function configure()
    {
        $this
            ->setName('open')
            ->setDescription('Open the specified library / service in PhpStorm')
            ->addArgument('library', InputArgument::OPTIONAL, 'The name of the library or service')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->setupConsoleHelper($input, $output);

        if (!$project = $this->getActiveProject()) {
            $this->tools()->error('there is no project currently active');
            $this->tools()->newline();

            return 1;
        }

        $this->tools()->warning('active project is <info>%s</info>', $project->name());

        $library = $input->getArgument('library');
        if (!$library) {
            $libs = $project
                ->libraries()->list()->keys()
                ->map(function ($value) { return $value . ' (lib)';})
                ->merge($project->services()->list()->keys()->map(function ($value) { return $value . ' (service)';}))
                ->sortByValue()
                ->values()
            ;

            $library = trim(
                str_replace(
                    ['(lib)', '(service)'], '', $this->tools()->choose('Select the library/service to open: ', $libs->toArray())
                )
            );
        }

        if (!$resource = $project->services()->get($library)) {
            if (!$resource = $project->libraries()->get($library)) {
                $this->tools()->error('did not find <info>%s</info> in libraries or services', $library);
                $this->tools()->newline();

                return 1;
            }
        }

        $this->tools()->info('opening <info>%s</info> in PhpStorm', $resource->name());
        $this->tools()->execute(sprintf('phpstorm %s', $resource->installPath()), $resource->installPath());
        $this->tools()->newline();

        return 0;
    }
}
