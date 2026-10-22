<?php declare(strict_types=1);

namespace Webwerkwien\ContaoConshoLicensecheckBundle\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Webwerkwien\ContaoConshoLicensecheckBundle\Console\SecretInput;
use Webwerkwien\ContaoConshoLicensecheckBundle\License\LicenseEntry;

/**
 * Enters a licence key (L7), read hidden from STDIN, never as an argument —
 * then fetches at once and names the outcome. The key is never printed.
 */
#[AsCommand(name: 'consho:licensecheck:set', description: 'Enters a licence key (typed hidden or piped in on STDIN, never as an argument) and confirms it at once')]
#[\Webwerkwien\ContaoAiCoreBundle\Attribute\AiContract(
    writes: true,
    tables: ['tl_consho_licensecheck', 'tl_consho_licensecheck_installation'],
    trace: [],
    irreversible: 'Sends the key to the shop named in it, which gives this installation the licence\'s place if nobody holds it; only a release frees it again.',
    repeatable: true,
    answerShape: ['status', 'product', 'name', 'outcome'],
)]
class SetKeyCommand extends Command
{
    public function __construct(
        private readonly LicenseEntry $entry,
        private readonly SecretInput $secret = new SecretInput(),
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $key = $this->secret->read($input, $output, 'Licence key');
        $entered = $this->entry->enter($key, time());

        $output->writeln(json_encode(['status' => 'set'] + $entered, \JSON_PRETTY_PRINT | \JSON_UNESCAPED_SLASHES | \JSON_UNESCAPED_UNICODE | \JSON_THROW_ON_ERROR));

        return Command::SUCCESS;
    }
}
