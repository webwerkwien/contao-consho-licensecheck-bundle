<?php declare(strict_types=1);

namespace Webwerkwien\ContaoConshoLicensecheckBundle\License;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Webwerkwien\ContaoConshoLicensecheckBundle\Product\Products;
use Webwerkwien\ContaoConshoLicensecheckBundle\Signature\SignedText;
use Webwerkwien\ContaoConshoLicensecheckBundle\Store\Installation;
use Webwerkwien\ContaoConshoLicensecheckBundle\Store\LicenseStore;

/**
 * The one place a paid extension asks before what it locks (block licence,
 * 4.5): allows(product) — offline, from what is stored, checked again at
 * every call.
 *
 * Allowed when `APP_ENV=dev` (L4), or when the stored confirmation is signed
 * by a key the product trusts, belongs to its stored key and to THIS
 * installation, says `active`, and its proof has not run out. Everything
 * the database holds is checked again, not believed: a row edited by hand
 * opens nothing.
 *
 * What is locked is the extension's business: Connect its write commands,
 * the licensing package `publish` and the like (L16).
 */
class LicenseGate
{
    // A clock more than a day behind the latest confirmation seen voids it.
    public const CLOCK_TOLERANCE = 86400;

    public function __construct(
        private readonly Products $products,
        private readonly LicenseStore $store,
        private readonly Installation $installation,
        private readonly LicenseKeys $keys,
        #[Autowire('%kernel.environment%')]
        private readonly string $environment,
    ) {
    }

    public function verdict(string $productKey, int $now): Verdict
    {
        if ('dev' === $this->environment) {
            return new Verdict(true, 'dev');
        }

        $product = $this->products->get($productKey);

        if (null === $product) {
            return new Verdict(false, 'unknownProduct');
        }

        $row = $this->store->row($productKey);
        $key = null === $row ? null : $this->keys->readFor($product, (string) $row['licenseKey']);

        if (null === $row || '' === (string) $row['licenseKey']) {
            return new Verdict(false, 'noKey');
        }

        if (null === $key) {
            return new Verdict(false, 'invalidKey');
        }

        if ('' === (string) $row['confirmation']) {
            return new Verdict(false, 'notConfirmed');
        }

        $confirmation = SignedText::read(SignedText::CONFIRMATION, (string) $row['confirmation'], $product->publicKeys());

        if (null === $confirmation || ($confirmation['lic'] ?? null) !== $key['lic'] || ($confirmation['ins'] ?? null) !== $this->installation->id()
            || !\is_int($confirmation['checkedAt'] ?? null) || !\is_int($confirmation['proofUntil'] ?? null) || !\is_string($confirmation['state'] ?? null)) {
            return new Verdict(false, 'invalidConfirmation');
        }

        $activeUntil = \is_int($confirmation['activeUntil'] ?? null) ? $confirmation['activeUntil'] : null;
        $seen = max((int) $row['seenCheckedAt'], $confirmation['checkedAt']);

        if ($now < $seen - self::CLOCK_TOLERANCE) {
            return new Verdict(false, 'clock', $activeUntil, $confirmation['proofUntil']);
        }

        if ('active' !== $confirmation['state']) {
            return new Verdict(false, $confirmation['state'], $activeUntil, $confirmation['proofUntil']);
        }

        if ($now > $confirmation['proofUntil']) {
            return new Verdict(false, 'proofExpired', $activeUntil, $confirmation['proofUntil']);
        }

        return new Verdict(true, 'active', $activeUntil, $confirmation['proofUntil']);
    }

    /**
     * For the extension's lock: nothing when allowed, otherwise the refusal
     * as contao-ai expects it (a plain InvalidArgumentException, LZ-5).
     *
     * @throws \InvalidArgumentException
     */
    public function assertAllowed(string $productKey, int $now): void
    {
        $verdict = $this->verdict($productKey, $now);

        if ($verdict->allowed) {
            return;
        }

        $product = $this->products->get($productKey);
        $reasons = $GLOBALS['TL_LANG']['CONSHO_LICENSECHECK']['reason'] ?? [];

        throw new \InvalidArgumentException(\sprintf(
            '%s: %s',
            null === $product ? $productKey : $product->name(),
            (string) ($reasons[$verdict->reason] ?? 'licence check: '.$verdict->reason),
        ));
    }
}
