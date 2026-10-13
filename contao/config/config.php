<?php declare(strict_types=1);

/*
 * "System → Lizenzen" (block licence, 4.5): no table of its own, hence a
 * `callback`. Under System, not under a Consho group — the checker does not
 * depend on the Consho core (L18), and extensions of other vendors use it.
 */
$GLOBALS['BE_MOD']['system']['consho_licensecheck'] = [
    'callback' => Webwerkwien\ContaoConshoLicensecheckBundle\Controller\Backend\LicenseModule::class,
];
