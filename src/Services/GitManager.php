<?php declare(strict_types=1);

namespace Somnambulist\ProjectManager\Services;

use Symfony\Component\Console\Helper\ProcessHelper;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;
use function sprintf;
use function trim;

/**
 * Class GitManager
 *
 * @package    Somnambulist\ProjectManager\Services
 * @subpackage Somnambulist\ProjectManager\Services\GitManager
 */
class GitManager
{

    /**
     * @var ProcessHelper|null
     */
    private $helper;

    /**
     * @var OutputInterface
     */
    private $output;

    /**
     * Constructor
     *
     * @param ProcessHelper|null   $helper
     * @param OutputInterface|null $output
     */
    public function __construct(ProcessHelper $helper = null, OutputInterface $output = null)
    {
        $this->helper = $helper;
        $this->output = $output;
    }

    public function add(string $cwd): bool
    {
        $proc = $this->exec('git add -A', $cwd);

        return $proc->isSuccessful();
    }

    public function clone(string $cwd, string $remote, string $local): bool
    {
        $proc = $this->exec('git clone %s %s', $cwd, $remote, $local);

        return $proc->isSuccessful();
    }

    public function commit(string $cwd, string $message = ''): bool
    {
        $proc = $this->exec('git commit -m \'%s\'', $cwd, $message);

        return $proc->isSuccessful();
    }

    public function init(string $cwd): bool
    {
        $proc = $this->exec('git init', $cwd);

        return $proc->isSuccessful();
    }

    public function pull(string $cwd, string $remote = 'origin', string $branch = 'master'): bool
    {
        $proc = $this->exec('git pull %s %s', $cwd, $remote, $branch);

        return $proc->isSuccessful();
    }

    public function push(string $cwd, string $remote = 'origin', string $branch = 'master'): bool
    {
        $proc = $this->exec('git push %s %s', $cwd, $remote, $branch);

        return $proc->isSuccessful();
    }

    public function isClean(string $cwd): bool
    {
        $proc = $this->exec('git status -s', $cwd);

        return !empty(trim($proc->getOutput()));
    }

    public function hasRemote(string $cwd): bool
    {
        $proc = $this->exec('git remote -v', $cwd);

        return !empty(trim($proc->getOutput()));
    }

    public function addRemote(string $cwd, string $name, string $remote): bool
    {
        $proc = $this->exec('git remote add %s %s', $cwd, $name, $remote);

        return $proc->isSuccessful();
    }

    public function setRemote(string $cwd, string $name, string $remote): bool
    {
        $proc = $this->exec('git remote set-url %s %s', $cwd, $name, $remote);

        return $proc->isSuccessful();
    }
    
    private function exec(string $command, string $cwd, ...$args): Process
    {
        $proc = Process::fromShellCommandline(trim(sprintf($command, ...$args)), $cwd, null, null, null);
            
        if ($this->helper instanceof ProcessHelper) {
            $this->helper->run($this->output, $proc);
        } else {
            $proc->run();
        }
        
        return $proc;
    }
}
