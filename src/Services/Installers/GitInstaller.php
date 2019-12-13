<?php declare(strict_types=1);

namespace Somnambulist\ProjectManager\Services\Installers;

use IlluminateAgnostic\Str\Support\Str;
use Somnambulist\ProjectManager\Models\Project;
use Somnambulist\ProjectManager\Models\Template;
use function sprintf;

/**
 * Class GitInstaller
 *
 * @package    Somnambulist\ProjectManager\Services\Installers
 * @subpackage Somnambulist\ProjectManager\Services\Installers\GitInstaller
 */
class GitInstaller extends AbstractInstaller
{

    public function installInto(Project $project, Template $template, string $name, string $cwd): int
    {
        $step = 0;

        $this->tools()->step(++$step, 'creating <info>%s</info> from git repository', $this->type);

        $res = $this->tools()->execute(
            sprintf(
                'git clone --depth=1 %s %s',
                Str::replaceFirst('git:', '', $template->source()),
                $cwd
            ),
            dirname($cwd)
        );

        if (!$res) {
            $this->tools()->error('failed to clone git repository, re-run with <info>-vvv</info> to get debugging');
            $this->tools()->info('do you have permission to access the repository?');
            $this->tools()->newline();

            return 1;
        }

        if (!$this->tools()->execute(sprintf('rm -rf %s/.git', $cwd))) {
            $this->tools()->error('failed to remove .git from <comment>%s</comment>', $cwd);
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
