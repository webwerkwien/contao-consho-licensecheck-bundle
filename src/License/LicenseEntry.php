<?php declare(strict_types=1);

namespace Webwerkwien\ContaoConshoLicensecheckBundle\License;

use Webwerkwien\ContaoConshoLicensecheckBundle\Store\LicenseStore;

/**
 * Entering a key (L7): checked offline first — a wrong key is not stored —,
 * then fetched at once, and the outcome named. One entry for every
 * extension: the key says which one it is for (L19).
 */
class LicenseEntry
{
    // The exception's code when the text is no key; the back end puts its
    // own sentence in place of the English one (LZ-7). A code, not a
    // subclass: contao-ai reads exactly \InvalidArgumentException as refusal.
    public const NOT_A_KEY = 1;

    public function __construct(
        private readonly LicenseKeys $keys,
        private readonly LicenseStore $store,
        private readonly LicenseFetcher $fetcher,
    ) {
    }

    /**
     * @throws \InvalidArgumentException when it is no key for an extension installed here
     *
     * @return array{product: string, name: string, outcome: string}
     */
    public function enter(string $key, int $now): array
    {
        $key = trim($key);
        $read = $this->keys->read($key);

        if (null === $read) {
            throw new \InvalidArgumentException('This is not a licence key for any paid extension installed here. Check that it was copied completely.', self::NOT_A_KEY);
        }

        $product = $read['product']->productKey();
        $this->store->setKey($product, $key, $now);

        return ['product' => $product, 'name' => $read['product']->name(), 'outcome' => $this->fetcher->fetch($product, $now)];
    }
}
