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

// Start page note while still allowed, %s is the extension's name.
$GLOBALS['TL_LANG']['CONSHO_LICENSECHECK']['stale'] = '%s: no confirmation from the licence server for 7 days. It locks after 14 days — use "Check now" under System → Licences.';
