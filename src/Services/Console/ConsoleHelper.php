<?php declare(strict_types=1);

namespace Somnambulist\ProjectManager\Services\Console;

use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\Question;
use function sprintf;

/**
 * Class OutputHelper
 *
 * @package    Somnambulist\ProjectManager\Services\Console
 * @subpackage Somnambulist\ProjectManager\Services\Console\OutputHelper
 */
class ConsoleHelper
{

    /**
     * @var InputInterface
     */
    private $input;

    /**
     * @var OutputInterface
     */
    private $output;

    /**
     * Constructor
     *
     * @param InputInterface  $input
     * @param OutputInterface $output
     */
    public function __construct(InputInterface $input, OutputInterface $output)
    {
        $this->input  = $input;
        $this->output = $output;
    }

    public function ask(string $question, bool $confirm = true)
    {
        $q      = new QuestionHelper();
        $result = $q->ask($this->input, $this->output, new Question('<q> Q </q> ' . $question));

        if ($confirm) {
            $conf = new Question(sprintf('<i> ▲ </i> You provided "<info>%s</info>", is this correct? [y/n] ', $result));

            if ('n' === $q->ask($this->input, $this->output, $conf)) {
                return $this->ask($question, $confirm);
            }
        }

        return $result;
    }

    public function choose(string $question, array $choices = [], $default = null)
    {
        $q = new QuestionHelper();
        $c = new ChoiceQuestion('<q> Q </q> ' . $question, $choices, $default);

        return $q->ask($this->input, $this->output, $c);
    }

    public function warning(string $message, ...$args): void
    {
        $this->output->writeln(sprintf('<i> ▲ </i> ' . $message, ...$args));
    }

    public function success(string $message, ...$args): void
    {
        $this->output->writeln(sprintf('<ok> ✔ </ok> ' . $message, ...$args));
    }

    public function error(string $message, ...$args): void
    {
        $this->output->writeln(sprintf('<err> ✖ </err> ' . $message, ...$args));
    }

    public function step($step, string $message, ...$args): void
    {
        $this->output->writeln(sprintf('<step> %s </step> ' . $message, $step, ...$args));
    }

    public function question(string $message, ...$args): void
    {
        $this->output->writeln(sprintf('<q> Q </q> ' . $message, ...$args));
    }

    public function newline(): void
    {
        $this->output->writeln('');
    }
}
