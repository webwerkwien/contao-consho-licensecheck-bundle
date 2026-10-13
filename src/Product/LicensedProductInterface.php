<?php declare(strict_types=1);

namespace Webwerkwien\ContaoConshoLicensecheckBundle\Product;

use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

/**
 * A paid extension that registers with the checker (L19): implement it as a
 * service and the checker keeps a key, fetches a confirmation and answers
 * allows() for it.
 *
 * The public keys come from here — from the extension's code, never from
 * the installation's configuration (P1).
 */
#[AutoconfigureTag(self::TAG)]
interface LicensedProductInterface
{
    public const TAG = 'consho_licensecheck.product';

    /**
     * The product id in the key (`prd`), e.g. "consho-connect".
     */
    public function productKey(): string;

    /**
     * What the back end page and the start page call it.
     */
    public function name(): string;

    /**
     * The installed version, sent with every confirmation (L8).
     */
    public function version(): string;

    /**
     * The public keys of the shop that sells it.
     *
     * @return array<string, string> key id => base64 Ed25519 public key
     */
    public function publicKeys(): array;
}
