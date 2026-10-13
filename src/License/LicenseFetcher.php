<?php declare(strict_types=1);

namespace Webwerkwien\ContaoConshoLicensecheckBundle\License;

use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Webwerkwien\ContaoConshoLicensecheckBundle\Product\Products;
use Webwerkwien\ContaoConshoLicensecheckBundle\Signature\SignedText;
use Webwerkwien\ContaoConshoLicensecheckBundle\Store\Installation;
use Webwerkwien\ContaoConshoLicensecheckBundle\Store\LicenseStore;

/**
 * Fetches the confirmation of a product from the address in its key (block
 * licence, 4.3/4.5): sends the key, the installation id and the version — no
 * domain, nothing about the shop (L8, LZ-3 E1).
 *
 * Keeps the previous confirmation whenever the answer brings nothing
 * trustworthy: not reachable, throttled (429), `unknown` (an older backup in
 * the shop, LZ-3 E2) or not signed by a trusted key. The old one then carries
 * on until its `proofUntil` — at most 14 days, never past the end.
 */
class LicenseFetcher
{
    private const TIMEOUT = 10;

    public function __construct(
        private readonly Products $products,
        private readonly LicenseStore $store,
        private readonly Installation $installation,
        private readonly LicenseKeys $keys,
        private readonly HttpClientInterface $http,
    ) {
    }

    /**
     * @return string the state answered (active, resting, expired, revoked, taken)
     *                or why nothing changed (noKey, invalidKey, unknown, throttled, unreachable, invalidAnswer)
     */
    public function fetch(string $productKey, int $now): string
    {
        return $this->ask($productKey, $now, false);
    }

    /**
     * Frees this installation's place in the shop and forgets the key here
     * ("Installation freigeben" from the installation itself). The local
     * state goes even when the shop is not reachable — the customer moves,
     * and the shop's own "Installation freigeben" remains.
     */
    public function release(string $productKey, int $now): string
    {
        $outcome = $this->ask($productKey, $now, true);
        $this->store->forget($productKey);

        return $outcome;
    }

    private function ask(string $productKey, int $now, bool $release): string
    {
        $product = $this->products->get($productKey);
        $row = $this->store->row($productKey);

        if (null === $product || null === $row || '' === (string) $row['licenseKey']) {
            return 'noKey';
        }

        $key = $this->keys->readFor($product, (string) $row['licenseKey']);

        if (null === $key) {
            $this->store->failed($productKey, 'invalidKey', $now);

            return 'invalidKey';
        }

        $installation = $this->installation->id();

        try {
            $response = $this->http->request('POST', $key['url'], [
                'json' => ['key' => (string) $row['licenseKey'], 'ins' => $installation, 'ver' => substr($product->version(), 0, 32)] + ($release ? ['release' => true] : []),
                'timeout' => self::TIMEOUT,
                'max_redirects' => 0,
            ]);
            $status = $response->getStatusCode();
            $body = 200 === $status ? $response->getContent(false) : '';
        } catch (ExceptionInterface) {
            return $this->failed($productKey, 'unreachable', $now);
        }

        if (429 === $status) {
            return $this->failed($productKey, 'throttled', $now);
        }

        if (200 !== $status) {
            return $this->failed($productKey, 'unreachable', $now);
        }

        $confirmation = SignedText::read(SignedText::CONFIRMATION, $body, $product->publicKeys());

        if (null === $confirmation || ($confirmation['lic'] ?? null) !== $key['lic'] || ($confirmation['ins'] ?? null) !== $installation
            || !\is_string($confirmation['state'] ?? null) || !\is_int($confirmation['checkedAt'] ?? null)) {
            return $this->failed($productKey, 'invalidAnswer', $now);
        }

        if ('unknown' === $confirmation['state']) {
            return $this->failed($productKey, 'unknown', $now);
        }

        $this->store->saveConfirmation($productKey, trim($body), $confirmation['checkedAt'], $now);

        return $confirmation['state'];
    }

    private function failed(string $productKey, string $error, int $now): string
    {
        $this->store->failed($productKey, $error, $now);

        return $error;
    }
}
