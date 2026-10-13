<?php declare(strict_types=1);

// Why a paid extension is locked (LicenseGate): the sentence after its name,
// in the refusal of a command and on the start page.
$GLOBALS['TL_LANG']['CONSHO_LICENSECHECK']['reason'] = [
    'unknownProduct' => 'Diese Erweiterung ist beim Lizenzprüfer nicht angemeldet.',
    'noKey' => 'Es ist kein Lizenzschlüssel eingetragen. Unter System → Lizenzen eintragen.',
    'invalidKey' => 'Der eingetragene Lizenzschlüssel gilt für diese Version nicht. Unter System → Lizenzen neu eintragen.',
    'notConfirmed' => 'Der Lizenzschlüssel ist noch nicht bestätigt. Unter System → Lizenzen „Jetzt prüfen“.',
    'invalidConfirmation' => 'Die gespeicherte Bestätigung passt nicht zu Schlüssel oder Installation. Unter System → Lizenzen „Jetzt prüfen“.',
    'clock' => 'Die Uhr des Servers geht mehr als einen Tag nach. Bitte die Uhrzeit des Servers prüfen.',
    'resting' => 'Das Abo ruht wegen einer offenen Rechnung. Nach der Zahlung unter System → Lizenzen „Jetzt prüfen“.',
    'revoked' => 'Die Lizenz ist gesperrt oder widerrufen.',
    'expired' => 'Die Lizenz ist abgelaufen.',
    'taken' => 'Der Lizenzschlüssel ist an einer anderen Installation aktiv. In „Meine Bestellungen“ des Shops oder an der anderen Installation freigeben.',
    'proofExpired' => 'Seit 14 Tagen keine Bestätigung vom Lizenzserver. Unter System → Lizenzen „Jetzt prüfen“.',
];
