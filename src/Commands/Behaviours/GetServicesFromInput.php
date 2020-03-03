<?php declare(strict_types=1);

namespace Somnambulist\ProjectManager\Commands\Behaviours;

use Somnambulist\Collection\MutableCollection;
use Somnambulist\ProjectManager\Models\Config;
use Somnambulist\ProjectManager\Services\Console\ConsoleHelper;
use Symfony\Component\Console\Input\InputInterface;
use function strtolower;
use function trim;

/**
 * Trait GetServicesFromInput
 *
 * @package Somnambulist\ProjectManager\Commands\Behaviours
 * @subpackage Somnambulist\ProjectManager\Commands\Behaviours\GetServicesFromInput
 *
 * @method ConsoleHelper tools()
 *
 * @property-read Config $config
 */
trait GetServicesFromInput
{

    protected function getServicesFrom(InputInterface $input, string $allMessage, string $chooseMessage = 'Select the services to work with: '): MutableCollection
    {
        $services = $input->getArgument('service');

        if (empty($services)) {
            $libs = $this->config->projects()->active()->services()->list()->sortByKey()->keys()->add('all');

            $services = [$this->tools()->choose($chooseMessage, $libs->toArray())];
        }

        if (strtolower(trim($services[0])) === 'all') {
            $this->tools()->info($allMessage);

            return $this->config->projects()->active()->services()->list()->sortByKey()->keys();
        }

        return new MutableCollection($services);
    }
}
