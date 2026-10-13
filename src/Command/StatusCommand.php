<?php declare(strict_types=1);

namespace Webwerkwien\ContaoConshoLicensecheckBundle\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Webwerkwien\ContaoConshoLicensecheckBundle\License\LicenseStatus;
use Webwerkwien\ContaoConshoLicensecheckBundle\Store\Installation;

/**
 * The licence state of every paid extension, as JSON — never the key.
 */
#[AsCommand(name: 'consho:licensecheck:status', description: 'Licence state of every paid extension installed here, as JSON (never the key)')]
#[\Webwerkwien\ContaoAiCoreBundle\Attribute\AiContract(
    writes: false,
    tables: ['tl_consho_licensecheck', 'tl_consho_licensecheck_installation'],
    answerShape: ['installation', 'licenses'],
)]
class StatusCommand extends Command
{
    public function __construct(
        private readonly LicenseStatus $status,
        private readonly Installation $installation,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $date = static fn (int|null $t): string|null => null === $t ? null : gmdate('Y-m-d\TH:i:s\Z', $t);
        $licenses = array_map(static fn (array $s): array => ['activeUntil' => $date($s['activeUntil']), 'proofUntil' => $date($s['proofUntil']), 'fetchedAt' => $date($s['fetchedAt'])] + $s, $this->status->all(time()));

        $output->writeln(json_encode(['installation' => $this->installation->id(), 'licenses' => $licenses], \JSON_PRETTY_PRINT | \JSON_UNESCAPED_SLASHES | \JSON_UNESCAPED_UNICODE | \JSON_THROW_ON_ERROR));

        return Command::SUCCESS;
    }
}
