<?php declare(strict_types=1);

namespace Webwerkwien\ContaoConshoLicensecheckBundle\Cron;

use Contao\CoreBundle\DependencyInjection\Attribute\AsCronJob;
use Webwerkwien\ContaoConshoLicensecheckBundle\License\LicenseFetcher;
use Webwerkwien\ContaoConshoLicensecheckBundle\Product\Products;
use Webwerkwien\ContaoConshoLicensecheckBundle\Store\Installation;
use Webwerkwien\ContaoConshoLicensecheckBundle\Store\LicenseStore;

/**
 * The daily confirmation (block licence, 4.5; LZ-3 "Last"): Contao's hourly
 * cron, and each installation asks in its own hour of the day, derived from
 * its id — a thousand installations whose system cron fires at 03:00 do not
 * all ask in the same minute.
 *
 * Not while the latest confirmation is younger than 20 hours. Past 26 hours
 * the hour no longer matters: a missed slot (server down, cron not running
 * that hour) is caught up at the next run, and failed attempts are retried
 * hourly from then on.
 */
#[AsCronJob('hourly')]
class LicenseCron
{
    public const FRESH = 20 * 3600;

    public const OVERDUE = 26 * 3600;

    public function __construct(
        private readonly Products $products,
        private readonly LicenseStore $store,
        private readonly Installation $installation,
        private readonly LicenseFetcher $fetcher,
    ) {
    }

    public function __invoke(): void
    {
        $this->run(time());
    }

    /**
     * @return list<string> the products asked — not returned when one failed: then that failure is thrown, after all were asked
     */
    public function run(int $now): array
    {
        $asked = [];
        $failure = null;
        $slot = self::slotOf($this->installation->id());

        foreach (array_keys($this->products->all()) as $product) {
            $row = $this->store->row($product);

            if (null === $row || '' === (string) $row['licenseKey'] || !self::due((int) $row['fetchedAt'], $slot, $now)) {
                continue;
            }

            // One extension's failure does not keep the others from asking
            // (LZ-7); the first is thrown afterwards, so it is logged.
            try {
                $this->fetcher->fetch($product, $now);
                $asked[] = $product;
            } catch (\Throwable $e) {
                $failure ??= $e;
            }
        }

        if (null !== $failure) {
            throw $failure;
        }

        return $asked;
    }

    /**
     * The installation's hour of the day, 0–23.
     */
    public static function slotOf(string $installation): int
    {
        return hexdec(substr($installation, 0, 4)) % 24;
    }

    public static function due(int $fetchedAt, int $slot, int $now): bool
    {
        $age = $now - $fetchedAt;

        return $age >= self::OVERDUE || ($age >= self::FRESH && (int) gmdate('G', $now) === $slot);
    }
}
