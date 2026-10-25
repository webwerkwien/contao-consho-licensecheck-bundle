<?php declare(strict_types=1);

namespace Webwerkwien\ContaoConshoLicensecheckBundle\Console;

use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\StreamableInputInterface;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;

/**
 * Reads a licence key without it ever being an argument (L7): typed hidden
 * at a terminal, or one line piped in on STDIN. The pattern of Connect's
 * SecretInput (CW-5), kept here so the checker depends on nothing of
 * Connect's.
 *
 * Never from the command line — contao:ai:run writes the whole line to
 * tl_log, and shell history and chat transcripts keep it. Never in the web
 * process: a key typed into a chat is a key in a transcript.
 */
final class SecretInput
{
    public function __construct(private readonly string $sapi = \PHP_SAPI)
    {
    }

    /**
     * @throws \InvalidArgumentException
     */
    public function read(InputInterface $input, OutputInterface $output, string $label): string
    {
        if ('cli' !== $this->sapi) {
            throw new \InvalidArgumentException('The licence key is taken on the console only, through STDIN — not in the chat. Enter it under System → Licences or on the console.');
        }

        $given = $input instanceof StreamableInputInterface ? $input->getStream() : null;

        // contao:ai:run starts a command from a StringInput and passes no
        // STDIN on. Secrets never go through contao-ai, so this refuses at
        // once and names the way over SSH. A 60 s hang on such a call comes
        // from Windows' ssh.exe not passing an empty stdin's end on, not from
        // the server.
        if (null === $given && $input instanceof StringInput) {
            throw new \InvalidArgumentException('The licence key needs the console\'s own STDIN, which contao-ai does not pass on: run "php bin/console consho:licensecheck:set" over SSH, or enter it under System → Licences.');
        }

        $stream = $given ?? \STDIN;

        if ($input->isInteractive() && stream_isatty($stream)) {
            $value = (string) (new QuestionHelper())->ask($input, $output, (new Question($label.': '))->setHidden(true)->setHiddenFallback(false));
        } else {
            $line = fgets($stream);
            $value = false === $line ? '' : rtrim($line, "\r\n");
        }

        $value = trim($value);

        if ('' === $value) {
            throw new \InvalidArgumentException('No licence key on STDIN. Type it at the prompt or pipe it in.');
        }

        return $value;
    }
}
