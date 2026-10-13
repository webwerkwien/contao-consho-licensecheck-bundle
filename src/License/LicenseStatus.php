<?php declare(strict_types=1);

namespace Webwerkwien\ContaoConshoLicensecheckBundle\License;

use Webwerkwien\ContaoConshoLicensecheckBundle\Product\Products;
use Webwerkwien\ContaoConshoLicensecheckBundle\Signature\SignedText;
use Webwerkwien\ContaoConshoLicensecheckBundle\Store\LicenseStore;

/**
 * The state of every paid extension, as the status command, the back end
 * page and the start page show it — the same facts in all three places.
 *
 * Never the key: what this returns may go to a chat (LZ-5). The licence id
 * is shown — it opens nothing without the key.
 */
class LicenseStatus
{
    // From here on the start page warns: the shop has not answered for a week.
    public const WARN_AFTER = 7 * 86400;

    public function __construct(
        private readonly Products $products,
        private readonly LicenseStore $store,
        private readonly LicenseGate $gate,
    ) {
    }

    /**
     * @return list<array{product: string, name: string, version: string, licenseId: string|null, allowed: bool, reason: string, activeUntil: int|null, proofUntil: int|null, fetchedAt: int|null, error: string, stale: bool}>
     */
    public function all(int $now): array
    {
        $states = [];

        foreach ($this->products->all() as $key => $product) {
            $row = $this->store->row($key);
            $verdict = $this->gate->verdict($key, $now);
            $licence = null === $row ? null : SignedText::read(SignedText::KEY, (string) $row['licenseKey'], $product->publicKeys());
            $fetchedAt = null === $row || 0 === (int) $row['fetchedAt'] ? null : (int) $row['fetchedAt'];

            $states[] = [
                'product' => $key,
                'name' => $product->name(),
                'version' => $product->version(),
                'licenseId' => \is_string($licence['lic'] ?? null) ? $licence['lic'] : null,
                'allowed' => $verdict->allowed,
                'reason' => $verdict->reason,
                'activeUntil' => $verdict->activeUntil,
                'proofUntil' => $verdict->proofUntil,
                'fetchedAt' => $fetchedAt,
                'error' => null === $row ? '' : (string) $row['error'],
                'stale' => null !== $fetchedAt && $now - $fetchedAt >= self::WARN_AFTER,
            ];
        }

        return $states;
    }
}
