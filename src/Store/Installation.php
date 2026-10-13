<?php declare(strict_types=1);

namespace Webwerkwien\ContaoConshoLicensecheckBundle\Store;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;

/**
 * The installation's id (L3): created at the first use, one row with id 1,
 * the same for every paid extension. A copy of the database carries it, so a
 * staging copy does not take a second place (L4).
 */
class Installation
{
    public function __construct(private readonly Connection $connection)
    {
    }

    public function id(): string
    {
        $id = $this->connection->fetchOne('SELECT installation FROM tl_consho_licensecheck_installation WHERE id = 1');

        if (\is_string($id) && 1 === preg_match('/^[0-9a-f]{32}$/', $id)) {
            return $id;
        }

        try {
            $this->connection->insert('tl_consho_licensecheck_installation', ['id' => 1, 'installation' => bin2hex(random_bytes(16)), 'createdAt' => time()]);
        } catch (UniqueConstraintViolationException) {
            // A second request created it in the same instant.
        }

        return (string) $this->connection->fetchOne('SELECT installation FROM tl_consho_licensecheck_installation WHERE id = 1');
    }
}
