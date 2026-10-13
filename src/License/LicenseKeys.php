<?php declare(strict_types=1);

namespace Webwerkwien\ContaoConshoLicensecheckBundle\License;

use Webwerkwien\ContaoConshoLicensecheckBundle\Product\LicensedProductInterface;
use Webwerkwien\ContaoConshoLicensecheckBundle\Product\Products;
use Webwerkwien\ContaoConshoLicensecheckBundle\Signature\SignedText;

/**
 * A licence key checked offline (L7): signed by a key pair the product
 * trusts, for exactly this product, with a licence id and an https address.
 * Which product a key is for follows from the key itself — one input field
 * for every extension (L19).
 */
class LicenseKeys
{
    public function __construct(private readonly Products $products)
    {
    }

    /**
     * @return array{product: LicensedProductInterface, lic: string, url: string}|null
     */
    public function read(string $key): array|null
    {
        foreach ($this->products->all() as $product) {
            $payload = $this->readFor($product, $key);

            if (null !== $payload) {
                return ['product' => $product] + $payload;
            }
        }

        return null;
    }

    /**
     * @return array{lic: string, url: string}|null
     */
    public function readFor(LicensedProductInterface $product, string $key): array|null
    {
        $payload = SignedText::read(SignedText::KEY, $key, $product->publicKeys());

        if (null === $payload || 1 !== ($payload['v'] ?? null) || ($payload['prd'] ?? null) !== $product->productKey()
            || !\is_string($payload['lic'] ?? null) || 1 !== preg_match('/^[0-9a-f]{32}$/', $payload['lic'])
            || !\is_string($payload['url'] ?? null) || !str_starts_with($payload['url'], 'https://')) {
            return null;
        }

        return ['lic' => $payload['lic'], 'url' => $payload['url']];
    }
}
