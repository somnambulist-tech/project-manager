<?php declare(strict_types=1);

namespace Somnambulist\ProjectManager\Services\Installers;

use IlluminateAgnostic\Str\Support\Str;
use Somnambulist\ProjectManager\Models\Project;
use Somnambulist\ProjectManager\Models\Template;
use function sprintf;

/**
 * Class ComposerInstaller
 *
 * @package    Somnambulist\ProjectManager\Services\Installers
 * @subpackage Somnambulist\ProjectManager\Services\Installers\ComposerInstaller
 */
class ComposerInstaller extends AbstractInstaller
{

    public function installInto(Project $project, Template $template, string $name, string $cwd): int
    {
        $step = 0;

        $this->tools()->step(++$step, 'creating <info>%s</info> from composer project', $this->type);
        $this->tools()->warning('installer scripts will not be run!');

        $res = $this->tools()->execute(
            sprintf(
                'composer create-project --no-scripts --remove-vcs %s %s',
                Str::replaceFirst('composer:', '', $template->source()),
                $cwd
            ),
            dirname($cwd)
        );

        if (!$res) {
            $this->tools()->error('failed to create project via composer, re-run with <info>-vvv</info> to get debugging');
            $this->tools()->newline();

            return 1;
        }

        $this->tools()->step(++$step, 'creating git repository');

        if (0 !== $this->initialiseGitRepositoryAt($cwd)) {
            return 1;
        }

        $this->updateProjectConfig($project, ++$step);

        return $this->success();
    }
}
