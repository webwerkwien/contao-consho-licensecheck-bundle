<?php declare(strict_types=1);

// Why a paid extension is locked (LicenseGate): the sentence after its name,
// in the refusal of a command and on the start page.
$GLOBALS['TL_LANG']['CONSHO_LICENSECHECK']['reason'] = [
    'unknownProduct' => 'This extension is not registered with the licence check.',
    'noKey' => 'No licence key has been entered. Enter it under System → Licences.',
    'invalidKey' => 'The licence key entered is not valid for this version. Enter it again under System → Licences.',
    'notConfirmed' => 'The licence key has not been confirmed yet. Use "Check now" under System → Licences.',
    'invalidConfirmation' => 'The stored confirmation does not match the key or this installation. Use "Check now" under System → Licences.',
    'clock' => 'The server clock is more than a day behind. Please check the server time.',
    'resting' => 'The subscription rests because of an open invoice. After paying, use "Check now" under System → Licences.',
    'revoked' => 'The licence has been locked or revoked.',
    'expired' => 'The licence has expired.',
    'taken' => 'The licence key is active on another installation. Release it in the shop\'s "My orders" or on the other installation.',
    'proofExpired' => 'No confirmation from the licence server for 14 days. Use "Check now" under System → Licences.',
];

// "System → Licences". array_replace, not +=: a language loaded after this
// one must be able to replace these sentences.
$GLOBALS['TL_LANG']['CONSHO_LICENSECHECK'] = array_replace($GLOBALS['TL_LANG']['CONSHO_LICENSECHECK'] ?? [], [
    'none' => 'No paid extension that needs a licence is installed here.',
    'colProduct' => 'Extension',
    'colState' => 'State',
    'colFetched' => 'Last confirmed',
    'licence' => 'Licence',
    'until' => 'until',
    'reasonOk' => ['active' => 'Licence valid', 'dev' => 'Development environment — nothing locked'],
    'outcome' => [
        'active' => 'Licence confirmed.',
        'resting' => 'The subscription rests because of an open invoice.',
        'expired' => 'The licence has expired.',
        'revoked' => 'The licence has been locked or revoked.',
        'taken' => 'The key is active on another installation — release it there or in the shop\'s "My orders".',
        'unknown' => 'The shop does not know this licence at the moment. The last confirmation stays valid; please tell the seller.',
        'throttled' => 'Too many requests — try again later.',
        'unreachable' => 'The licence server cannot be reached. Next attempt with the hourly cron.',
        'invalidAnswer' => 'The licence server\'s answer was not validly signed.',
        'invalidKey' => 'The key entered is not valid for this version.',
        'noKey' => 'No key entered.',
    ],
    'refresh' => 'Check now',
    'release' => 'Release installation',
    'releaseConfirm' => 'Release the installation? The key is deleted here and can then be entered in another installation. Until it is entered here again, the extension is locked.',
    'released' => '%s: installation released, the key has been deleted here.',
    'enterLegend' => 'Enter licence key',
    'enterLabel' => 'Licence key',
    'enterHelp' => 'From the shop\'s order page. The key itself says which extension it is for. It is checked at once and not shown again.',
    'enterSubmit' => 'Enter and check',
    'installation' => 'Installation id (random, says nothing about the site)',
]);

// Start page note while still allowed, %s is the extension's name.
$GLOBALS['TL_LANG']['CONSHO_LICENSECHECK']['stale'] = '%s: no confirmation from the licence server for 7 days. It locks after 14 days — use "Check now" under System → Licences.';
