<?php declare(strict_types=1);

namespace Somnambulist\ProjectManager\Services\Config;

use Somnambulist\ProjectManager\Models\Config;

/**
 * Class Factory
 *
 * @package    Somnambulist\ProjectManager\Services\Config
 * @subpackage Somnambulist\ProjectManager\Services\Config\Factory
 */
final class Factory
{

    public static function create(): Config
    {
        return (new ConfigParser())->parse((new ConfigLocator())->locate());
    }
}
