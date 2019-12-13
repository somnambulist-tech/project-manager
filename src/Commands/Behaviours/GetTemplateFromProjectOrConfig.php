<?php declare(strict_types=1);

namespace Somnambulist\ProjectManager\Commands\Behaviours;

use Somnambulist\ProjectManager\Models\Config;
use Somnambulist\ProjectManager\Models\Template;
use Somnambulist\ProjectManager\Services\Console\ConsoleHelper;

/**
 * Trait GetTemplateFromProjectOrConfig
 *
 * @package    Somnambulist\ProjectManager\Commands\Behaviours
 * @subpackage Somnambulist\ProjectManager\Commands\Behaviours\GetTemplateFromProjectOrConfig
 *
 * @property-read Config $config
 * @method ConsoleHelper tools()
 */
trait GetTemplateFromProjectOrConfig
{

    /**
     * @param string $template
     *
     * @return int|Template
     */
    protected function getTemplate(string $template)
    {
        if (null === $temp = $this->config->projects()->active()->templates()->get($template)) {
            if (null === $temp = $this->config->templates()->get($template)) {
                $this->tools()->error('there is no template <info>%s</info> in the project or the core', $template);
                $this->tools()->newline();

                return 1;
            }
        }

        return $temp;
    }
}
