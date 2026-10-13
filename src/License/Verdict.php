<?php declare(strict_types=1);

namespace Webwerkwien\ContaoConshoLicensecheckBundle\License;

/**
 * What the gate says about a product at this moment, and why. The reason is
 * a code; the sentence comes from CONSHO_LICENSECHECK.reason in the language
 * files.
 */
final class Verdict
{
    public function __construct(
        public readonly bool $allowed,
        public readonly string $reason,
        public readonly int|null $activeUntil = null,
        public readonly int|null $proofUntil = null,
    ) {
    }
}
