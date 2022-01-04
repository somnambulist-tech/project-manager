<?php declare(strict_types=1);

namespace Somnambulist\ProjectManager\Commands\Behaviours;

use Somnambulist\Components\Collection\MutableCollection;
use Somnambulist\ProjectManager\Models\Config;
use Somnambulist\ProjectManager\Services\Console\ConsoleHelper;
use Symfony\Component\Console\Input\InputInterface;
use function strtolower;
use function trim;

/**
 * Trait GetLibrariesFromInput
 *
 * @package Somnambulist\ProjectManager\Commands\Behaviours
 * @subpackage Somnambulist\ProjectManager\Commands\Behaviours\GetLibrariesFromInput
 *
 * @method ConsoleHelper tools()
 *
 * @property-read Config $config
 */
trait GetLibrariesFromInput
{

    protected function getLibrariesFrom(InputInterface $input, string $allMessage, string $chooseMessage = 'Select the libraries to work with: '): MutableCollection
    {
        $libraries = $input->getArgument('library');

        if (empty($libraries)) {
            $libs = $this->config->projects()->active()->libraries()->list()->keys()->add('all');

            $libraries = [$this->tools()->choose($chooseMessage, $libs->toArray())];
        }

        if (strtolower(trim($libraries[0])) === 'all') {
            $this->tools()->info($allMessage);

            return $this->config->projects()->active()->libraries()->list()->keys();
        }

        return new MutableCollection($libraries);
    }
}
