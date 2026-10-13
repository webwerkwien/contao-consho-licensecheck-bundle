<?php declare(strict_types=1);

/*
 * The installation's id (L3): 128 random bits, created once, one for all
 * paid extensions. In the database, so a copy carries the same id and does
 * not take a second place (L4). Nothing about the site.
 */
$GLOBALS['TL_DCA']['tl_consho_licensecheck_installation'] = [
    'config' => [
        'dataContainer' => Contao\DC_Table::class,
        'closed' => true,
        'notEditable' => true,
        'notCopyable' => true,
        'notDeletable' => true,
        'sql' => ['keys' => ['id' => 'primary']],
    ],

    'fields' => [
        'id' => ['sql' => ['type' => 'integer', 'unsigned' => true]],
        'installation' => ['sql' => ['type' => 'string', 'length' => 32, 'fixed' => true, 'default' => '']],
        'createdAt' => ['sql' => ['type' => 'integer', 'unsigned' => true, 'default' => 0]],
    ],
];
