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
 * "Installation freigeben" from the installation itself (4.5): frees the
 * place in the shop and forgets the key here — before moving to a new
 * server.
 */
#[AsCommand(name: 'consho:licensecheck:release', description: 'Releases this installation\'s place for a paid extension and forgets its key here')]
#[\Webwerkwien\ContaoAiCoreBundle\Attribute\AiContract(
    writes: true,
    tables: ['tl_consho_licensecheck'],
    trace: [],
    irreversible: 'Frees this installation\'s place in the shop; the extension is locked here until its key is entered again.',
    repeatable: true,
    answerShape: ['status', 'product', 'shop'],
)]
class ReleaseCommand extends Command
{
    public function __construct(
        private readonly Products $products,
        private readonly LicenseFetcher $fetcher,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('product', InputArgument::REQUIRED, 'The product id, e.g. consho-connect');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $product = (string) $input->getArgument('product');

        if (null === $this->products->get($product)) {
            throw new \InvalidArgumentException(\sprintf('No paid extension "%s" is installed here.', $product));
        }

        $output->writeln(json_encode(['status' => 'released', 'product' => $product, 'shop' => $this->fetcher->release($product, time())], \JSON_PRETTY_PRINT | \JSON_UNESCAPED_SLASHES | \JSON_THROW_ON_ERROR));

        return Command::SUCCESS;
    }
}
