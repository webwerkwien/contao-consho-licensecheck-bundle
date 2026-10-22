<?php declare(strict_types=1);

namespace Webwerkwien\ContaoConshoLicensecheckBundle\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Webwerkwien\ContaoConshoLicensecheckBundle\License\LicenseFetcher;
use Webwerkwien\ContaoConshoLicensecheckBundle\Product\Products;

/**
 * "Jetzt prüfen": fetches the confirmation now, for one product or all.
 */
#[AsCommand(name: 'consho:licensecheck:refresh', description: 'Fetches the licence confirmation now, for one paid extension or all')]
#[\Webwerkwien\ContaoAiCoreBundle\Attribute\AiContract(
    writes: true,
    tables: ['tl_consho_licensecheck'],
    trace: [],
    irreversible: 'Sends each key to the shop named in it, which gives this installation the licence\'s place if nobody holds it; only a release frees it again.',
    repeatable: true,
    answerShape: ['outcomes'],
)]
class RefreshCommand extends Command
{
    public function __construct(
        private readonly Products $products,
        private readonly LicenseFetcher $fetcher,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('product', InputArgument::OPTIONAL, 'Only this product id; without: every paid extension with a key');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $only = $input->getArgument('product');

        if (\is_string($only) && null === $this->products->get($only)) {
            throw new \InvalidArgumentException(\sprintf('No paid extension "%s" is installed here: %s.', $only, implode(', ', array_keys($this->products->all())) ?: 'none'));
        }

        $outcomes = [];

        foreach (\is_string($only) ? [$only] : array_keys($this->products->all()) as $product) {
            $outcomes[$product] = $this->fetcher->fetch($product, time());
        }

        $output->writeln(json_encode(['outcomes' => $outcomes], \JSON_PRETTY_PRINT | \JSON_UNESCAPED_SLASHES | \JSON_THROW_ON_ERROR));

        return Command::SUCCESS;
    }
}
