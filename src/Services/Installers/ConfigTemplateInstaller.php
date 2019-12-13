<?php declare(strict_types=1);

namespace Somnambulist\ProjectManager\Services\Installers;

use Somnambulist\ProjectManager\Models\Project;
use Somnambulist\ProjectManager\Models\Template;
use function is_dir;
use function sprintf;
use const DIRECTORY_SEPARATOR;

/**
 * Class ConfigTemplateInstaller
 *
 * @package    Somnambulist\ProjectManager\Services\Installers
 * @subpackage Somnambulist\ProjectManager\Services\Installers\ConfigTemplateInstaller
 */
class ConfigTemplateInstaller extends AbstractInstaller
{

    public function installInto(Project $project, Template $template, string $name, string $cwd): int
    {
        $step = 0;

        $this->tools()->step(++$step, 'creating <info>%s</info> folder', $this->type);

        if (0 !== $this->createLibraryFolder($cwd)) {
            return 1;
        }

        $this->tools()->step(++$step, 'copying template files to %s', $this->type);

        $source = $project->configPath() . DIRECTORY_SEPARATOR . $template->source();

        if (!is_dir($source)) {
            $this->tools()->error('template folder not found at <comment>%s</comment>', $source);
            $this->tools()->newline();

            return 1;
        }

        $this->tools()->execute(sprintf('cp -R %s %s', $source, $cwd));

        $this->tools()->step(++$step, 'creating git repository');

        if (0 !== $this->initialiseGitRepositoryAt($cwd)) {
            return 1;
        }

        $this->updateProjectConfig($project, ++$step);

        return $this->success();
    }
}
