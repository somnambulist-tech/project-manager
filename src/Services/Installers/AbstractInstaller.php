<?php declare(strict_types=1);

namespace Somnambulist\ProjectManager\Services\Installers;

use Somnambulist\ProjectManager\Commands\Behaviours\CanCopyFromConfigTemplates;
use Somnambulist\ProjectManager\Commands\Behaviours\CanCreateLibraryOrServicesFolder;
use Somnambulist\ProjectManager\Commands\Behaviours\CanInitialiseGitRepository;
use Somnambulist\ProjectManager\Commands\Behaviours\CanUpdateProjectConfiguration;
use Somnambulist\ProjectManager\Services\Console\ConsoleHelper;
use function mkdir;
use function touch;
use const DIRECTORY_SEPARATOR;

/**
 * Class AbstractInstaller
 *
 * @package    Somnambulist\ProjectManager\Services\Installers
 * @subpackage Somnambulist\ProjectManager\Services\Installers\AbstractInstaller
 */
abstract class AbstractInstaller
{

    use CanCreateLibraryOrServicesFolder;
    use CanCopyFromConfigTemplates;
    use CanInitialiseGitRepository;
    use CanUpdateProjectConfiguration;

    /**
     * @var ConsoleHelper
     */
    protected $consoleHelper;

    /**
     * @var string
     */
    protected $type;

    /**
     * Constructor
     *
     * @param ConsoleHelper $helper
     * @param string        $type
     */
    public function __construct(ConsoleHelper $helper, string $type)
    {
        $this->consoleHelper = $helper;
        $this->type          = $type;
    }

    protected function tools(): ConsoleHelper
    {
        return $this->consoleHelper;
    }

    protected function success(): int
    {
        $this->tools()->success('%s scaffold created <info>successfully</info>', $this->type);
        $this->tools()->info('be sure to add the remote git repository once it is configured to the config file');
        $this->tools()->newline();

        return 0;
    }

    protected function createGitKeepAt($cwd): bool
    {
        return mkdir($cwd, 0775, true) && touch($cwd . DIRECTORY_SEPARATOR . '.gitkeep');
    }
}
