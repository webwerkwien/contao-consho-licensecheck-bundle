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

// "System → Lizenzen".
$GLOBALS['TL_LANG']['CONSHO_LICENSECHECK'] += [
    'none' => 'Hier ist keine bezahlte Erweiterung installiert, die eine Lizenz braucht.',
    'colProduct' => 'Erweiterung',
    'colState' => 'Zustand',
    'colFetched' => 'Zuletzt bestätigt',
    'licence' => 'Lizenz',
    'until' => 'bis',
    'reasonOk' => ['active' => 'Lizenz gültig', 'dev' => 'Entwicklungsumgebung — nichts gesperrt'],
    'outcome' => [
        'active' => 'Lizenz bestätigt.',
        'resting' => 'Das Abo ruht wegen einer offenen Rechnung.',
        'expired' => 'Die Lizenz ist abgelaufen.',
        'revoked' => 'Die Lizenz ist gesperrt oder widerrufen.',
        'taken' => 'Der Schlüssel ist an einer anderen Installation aktiv — dort oder in „Meine Bestellungen“ des Shops freigeben.',
        'unknown' => 'Der Shop kennt diese Lizenz gerade nicht. Die letzte Bestätigung gilt weiter; bitte beim Verkäufer melden.',
        'throttled' => 'Zu viele Anfragen — später noch einmal versuchen.',
        'unreachable' => 'Der Lizenzserver ist nicht erreichbar. Nächster Versuch beim stündlichen Cron.',
        'invalidAnswer' => 'Die Antwort des Lizenzservers war nicht gültig signiert.',
        'invalidKey' => 'Der eingetragene Schlüssel gilt für diese Version nicht.',
        'noKey' => 'Kein Schlüssel eingetragen.',
    ],
    'refresh' => 'Jetzt prüfen',
    'release' => 'Installation freigeben',
    'releaseConfirm' => 'Installation freigeben? Der Schlüssel wird hier gelöscht und kann danach in einer anderen Installation eingetragen werden. Bis er hier wieder eingetragen ist, ist die Erweiterung gesperrt.',
    'released' => '%s: Installation freigegeben, der Schlüssel ist hier gelöscht.',
    'enterLegend' => 'Lizenzschlüssel eintragen',
    'enterLabel' => 'Lizenzschlüssel',
    'enterHelp' => 'Von der Bestellseite des Shops. Zu welcher Erweiterung er gehört, steht im Schlüssel. Er wird sofort geprüft und danach nicht mehr angezeigt.',
    'enterSubmit' => 'Eintragen und prüfen',
    'installation' => 'Installations-Kennung (zufällig, sagt nichts über die Website)',
];

// Start page note while still allowed, %s is the extension's name.
$GLOBALS['TL_LANG']['CONSHO_LICENSECHECK']['stale'] = '%s: Seit 7 Tagen keine Bestätigung vom Lizenzserver. Nach 14 Tagen wird gesperrt — unter System → Lizenzen „Jetzt prüfen“.';
