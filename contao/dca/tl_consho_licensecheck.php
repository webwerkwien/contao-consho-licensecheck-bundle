<?php declare(strict_types=1);

/*
 * One row per paid extension installed here (L19, P2): its licence key and
 * the latest signed confirmation. In the database, not a file: a copy of the
 * database carries the same licence and does not count twice (L4).
 *
 * Written by the checker only — set, fetch, release. The back end page
 * "Lizenzen" shows it; there is no mask.
 */
$GLOBALS['TL_DCA']['tl_consho_licensecheck'] = [
    'config' => [
        'dataContainer' => Contao\DC_Table::class,
        'closed' => true,
        'notEditable' => true,
        'notCopyable' => true,
        'notDeletable' => true,
        'sql' => ['keys' => ['id' => 'primary', 'product' => 'unique']],
    ],

    'fields' => [
        'id' => ['sql' => ['type' => 'integer', 'unsigned' => true, 'autoincrement' => true]],
        'tstamp' => ['sql' => ['type' => 'integer', 'unsigned' => true, 'default' => 0]],
        // `prd` in the key.
        'product' => ['sql' => ['type' => 'string', 'length' => 64, 'default' => '']],
        'licenseKey' => ['sql' => ['type' => 'text', 'notnull' => false]],
        // The latest signed answer of the confirmation address, as received.
        'confirmation' => ['sql' => ['type' => 'text', 'notnull' => false]],
        // The highest `checkedAt` ever seen: a clock set back more than a
        // day behind it voids the confirmation.
        'seenCheckedAt' => ['sql' => ['type' => 'integer', 'unsigned' => true, 'default' => 0]],
        // The latest fetch that brought a signed answer.
        'fetchedAt' => ['sql' => ['type' => 'integer', 'unsigned' => true, 'default' => 0]],
        'attemptedAt' => ['sql' => ['type' => 'integer', 'unsigned' => true, 'default' => 0]],
        // Why the latest attempt brought nothing new: unreachable, throttled,
        // unknown, invalid. Empty after a success.
        'error' => ['sql' => ['type' => 'string', 'length' => 32, 'default' => '']],
    ],
];
