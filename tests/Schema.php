<?php declare(strict_types=1);

namespace Webwerkwien\ContaoConshoLicensecheckBundle\Tests;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Schema\Table;

/**
 * This package's tables for SQLite, built from its own DCA files — never a
 * hand-written copy. Its own small builder: the checker does not depend on
 * the Consho core and cannot borrow its DcaSchema.
 */
final class Schema
{
    public const TABLES = ['tl_consho_licensecheck', 'tl_consho_licensecheck_installation'];

    public static function connection(): Connection
    {
        $connection = DriverManager::getConnection(['driver' => 'pdo_sqlite', 'memory' => true]);

        foreach (self::TABLES as $name) {
            $GLOBALS['TL_DCA'] = [];
            require __DIR__.'/../contao/dca/'.$name.'.php';
            $dca = $GLOBALS['TL_DCA'][$name];
            $table = new Table($name);

            foreach ($dca['fields'] as $field => $config) {
                $sql = $config['sql'];
                $options = array_intersect_key($sql, array_flip(['length', 'default', 'notnull', 'autoincrement', 'unsigned', 'fixed']));
                $table->addColumn($field, $sql['type'], $options);
            }

            foreach ($dca['config']['sql']['keys'] as $columns => $kind) {
                $list = explode(',', $columns);
                match ($kind) {
                    'primary' => $table->setPrimaryKey($list),
                    'unique' => $table->addUniqueIndex($list),
                    default => $table->addIndex($list),
                };
            }

            foreach ($connection->getDatabasePlatform()->getCreateTableSQL($table) as $statement) {
                $connection->executeStatement($statement);
            }
        }

        unset($GLOBALS['TL_DCA']);

        return $connection;
    }
}
