<?php declare(strict_types=1);

namespace Webwerkwien\ContaoConshoLicensecheckBundle\Store;

use Doctrine\DBAL\Connection;

/**
 * The key and the latest confirmation of each paid extension (P2), one row
 * per product.
 */
class LicenseStore
{
    public function __construct(private readonly Connection $connection)
    {
    }

    /**
     * @return array<string, mixed>|null
     */
    public function row(string $product): array|null
    {
        $row = $this->connection->fetchAssociative('SELECT * FROM tl_consho_licensecheck WHERE product = ?', [$product]);

        return false === $row ? null : $row;
    }

    /**
     * A new key: the old confirmation belonged to the old key and goes.
     * The highest checkedAt stays — the clock check is the installation's.
     */
    public function setKey(string $product, string $key, int $now): void
    {
        $values = ['tstamp' => $now, 'licenseKey' => $key, 'confirmation' => null, 'fetchedAt' => 0, 'attemptedAt' => 0, 'error' => ''];

        if (null === $this->row($product)) {
            $this->connection->insert('tl_consho_licensecheck', ['product' => $product, 'seenCheckedAt' => 0] + $values);

            return;
        }

        $this->connection->update('tl_consho_licensecheck', $values, ['product' => $product]);
    }

    public function saveConfirmation(string $product, string $confirmation, int $checkedAt, int $now): void
    {
        $this->connection->executeStatement(
            "UPDATE tl_consho_licensecheck SET confirmation = ?, seenCheckedAt = CASE WHEN seenCheckedAt > ? THEN seenCheckedAt ELSE ? END, fetchedAt = ?, attemptedAt = ?, error = '', tstamp = ? WHERE product = ?",
            [$confirmation, $checkedAt, $checkedAt, $now, $now, $now, $product],
        );
    }

    public function failed(string $product, string $error, int $now): void
    {
        $this->connection->update('tl_consho_licensecheck', ['attemptedAt' => $now, 'error' => $error, 'tstamp' => $now], ['product' => $product]);
    }

    public function forget(string $product): void
    {
        $this->connection->delete('tl_consho_licensecheck', ['product' => $product]);
    }
}
