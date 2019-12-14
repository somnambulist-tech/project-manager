<?php declare(strict_types=1);

namespace Somnambulist\ProjectManager\Services\Installers;

use Somnambulist\ProjectManager\Models\Project;
use Somnambulist\ProjectManager\Models\Template;
use function array_map;
use function escapeshellarg;
use function file_exists;
use function implode;
use function is_array;
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

        $this->tools()->step(++$step, 'creating <info>%s</info> folder <info>%s</info>', $this->type, $cwd);

        if (0 !== $this->createLibraryFolder($cwd)) {
            return 1;
        }

        $this->tools()->step(++$step, 'copying template files to %s', $this->type);

        $source = $project->configPath() . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR . $template->source();

        if (!is_dir($source)) {
            $this->tools()->error('template folder not found at <comment>%s</comment>', $source);
            $this->tools()->newline();

            return 1;
        }

        $this->tools()->execute(sprintf('cp -r %s %s', $source . DIRECTORY_SEPARATOR . '.', $cwd));

        if (file_exists($cwd . DIRECTORY_SEPARATOR . 'post_copy.php')) {
            $this->postCopyAction($project, $template, $name, $cwd, $step);
        }

        $this->tools()->step(++$step, 'creating git repository');

        if (0 !== $this->initialiseGitRepositoryAt($cwd)) {
            return 1;
        }

        $this->updateProjectConfig($project, ++$step);

        return $this->success();
    }

    private function postCopyAction(Project $project, Template $template, string $name, string $cwd, &$step): void
    {
        $this->tools()->info('the template has a post copy action, preparing...');

        $args = [];

        if (file_exists($cwd . DIRECTORY_SEPARATOR . 'post_copy_args.php')) {
            $this->tools()->warning('the post copy action requires input to run');

            $tmp = include_once $cwd . DIRECTORY_SEPARATOR . 'post_copy_args.php';

            if (!is_array($tmp)) {
                return;
            }

            foreach ($tmp as $arg => $prompt) {
                $args[] = $this->tools()->ask($prompt);
            }
        }

        $this->tools()->step(++$step, 'running post copy action');
        $this->tools()->execute('php post_copy.php ' . implode(' ', array_map('escapeshellarg', $args)), $cwd);
    }
}
