<?php declare(strict_types=1);

namespace Somnambulist\ProjectManager\Contracts;

use Somnambulist\ProjectManager\Models\Templates;

/**
 * Interface TemplatableResource
 *
 * @package    Somnambulist\ProjectManager\Contracts
 * @subpackage Somnambulist\ProjectManager\Contracts\TemplatableResource
 */
interface TemplatableResource
{

    public function templates(): Templates;
}
