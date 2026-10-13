<?php declare(strict_types=1);

namespace Webwerkwien\ContaoConshoLicensecheckBundle\Signature;

/**
 * Reads the signed texts of block licence (4.3): `<kind>.<base64url JSON>.
 * <base64url Ed25519 signature over "<kind>.<base64url JSON>">`, with `kid` in
 * the JSON naming the key pair.
 *
 * The reading half only — the signing half stays in the shop
 * (contao-consho-licensing-bundle). Kept here on its own so the checker
 * depends on nothing but sodium; the format is fixed by the spec, and the
 * tests sign their own texts to the same rules.
 */
final class SignedText
{
    // A licence key — never changes.
    public const KEY = 'CONSHO1';

    // The daily confirmation.
    public const CONFIRMATION = 'CONSHOC1';

    /**
     * The payload when the text is of this kind and signed by one of the
     * keys, otherwise null — whatever is wrong with it.
     *
     * @param array<string, string> $publicKeys kid => base64 public key
     *
     * @return array<string, mixed>|null
     */
    public static function read(string $kind, string $text, array $publicKeys): array|null
    {
        $parts = explode('.', trim($text));

        if (3 !== \count($parts) || $kind !== $parts[0]) {
            return null;
        }

        $json = self::decode($parts[1]);
        $signature = self::decode($parts[2]);

        if (null === $json || null === $signature || \SODIUM_CRYPTO_SIGN_BYTES !== \strlen($signature)) {
            return null;
        }

        $payload = json_decode($json, true);

        if (!\is_array($payload) || !\is_string($payload['kid'] ?? null) || !isset($publicKeys[$payload['kid']])) {
            return null;
        }

        $public = base64_decode($publicKeys[$payload['kid']], true);

        if (false === $public || \SODIUM_CRYPTO_SIGN_PUBLICKEYBYTES !== \strlen($public) || !sodium_crypto_sign_verify_detached($signature, $kind.'.'.$parts[1], $public)) {
            return null;
        }

        return $payload;
    }

    private static function decode(string $text): string|null
    {
        if (1 !== preg_match('/^[A-Za-z0-9_-]+$/', $text)) {
            return null;
        }

        $bytes = base64_decode(strtr($text, '-_', '+/'), true);

        return false === $bytes ? null : $bytes;
    }
}
