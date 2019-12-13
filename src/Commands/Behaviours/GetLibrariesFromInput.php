<?php declare(strict_types=1);

namespace Somnambulist\ProjectManager\Commands\Behaviours;

use Somnambulist\Collection\MutableCollection;
use Somnambulist\ProjectManager\Models\Config;
use Symfony\Component\Console\Input\InputInterface;
use function strtolower;
use function trim;

/**
 * Trait GetLibrariesFromInput
 *
 * @package Somnambulist\ProjectManager\Commands\Behaviours
 * @subpackage Somnambulist\ProjectManager\Commands\Behaviours\GetLibrariesFromInput
 *
 * @property-read Config $config
 */
trait GetLibrariesFromInput
{

    protected function getLibrariesFrom(InputInterface $input, string $message): MutableCollection
    {
        $libraries = $input->getArgument('library');

        if (strtolower(trim($libraries[0])) === 'all') {
            $this->tools()->info($message);

            return $this->config->projects()->active()->libraries()->list()->keys();
        }

        return new MutableCollection($libraries);
    }
}
