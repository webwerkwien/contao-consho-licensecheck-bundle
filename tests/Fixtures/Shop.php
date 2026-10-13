<?php declare(strict_types=1);

namespace Webwerkwien\ContaoConshoLicensecheckBundle\Tests\Fixtures;

use Webwerkwien\ContaoConshoLicensecheckBundle\Product\LicensedProductInterface;

/**
 * A sales shop for the tests: a key pair that signs keys and confirmations
 * to the format of block licence 4.3 — the same rules the licensing package
 * signs by, written out here so the checker is tested against the format,
 * not against its own reader.
 */
final class Shop
{
    public readonly string $kid;

    private readonly string $secret;

    public readonly string $public;

    public function __construct()
    {
        $pair = sodium_crypto_sign_keypair();
        $this->public = sodium_crypto_sign_publickey($pair);
        $this->secret = sodium_crypto_sign_secretkey($pair);
        $this->kid = substr(hash('sha256', $this->public), 0, 16);
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function sign(string $kind, array $payload, string|null $kid = null): string
    {
        $json = self::encode((string) json_encode(['kid' => $kid ?? $this->kid] + $payload, \JSON_UNESCAPED_SLASHES));

        return $kind.'.'.$json.'.'.self::encode(sodium_crypto_sign_detached($kind.'.'.$json, $this->secret));
    }

    public function key(string $lic, string $product = 'acme-tool', string $url = 'https://shop.example.org/_consho/license/confirm'): string
    {
        return $this->sign('CONSHO1', ['v' => 1, 'lic' => $lic, 'prd' => $product, 'iat' => 1_000, 'url' => $url]);
    }

    /**
     * @param array<string, mixed> $overrides
     */
    public function confirmation(string $lic, string $ins, int $checkedAt, array $overrides = []): string
    {
        return $this->sign('CONSHOC1', $overrides + ['v' => 1, 'lic' => $lic, 'ins' => $ins, 'state' => 'active', 'activeUntil' => null, 'checkedAt' => $checkedAt, 'proofUntil' => $checkedAt + 14 * 86400]);
    }

    public function product(string $productKey = 'acme-tool'): LicensedProductInterface
    {
        $public = base64_encode($this->public);
        $kid = $this->kid;

        return new class($productKey, $kid, $public) implements LicensedProductInterface {
            public function __construct(private readonly string $key, private readonly string $kid, private readonly string $public)
            {
            }

            public function productKey(): string
            {
                return $this->key;
            }

            public function name(): string
            {
                return 'Acme Tool';
            }

            public function version(): string
            {
                return '2.1.0';
            }

            public function publicKeys(): array
            {
                return [$this->kid => $this->public];
            }
        };
    }

    private static function encode(string $bytes): string
    {
        return rtrim(strtr(base64_encode($bytes), '+/', '-_'), '=');
    }
}
