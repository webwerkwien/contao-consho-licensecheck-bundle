<?php declare(strict_types=1);

namespace Webwerkwien\ContaoConshoLicensecheckBundle\Controller\Backend;

use Contao\BackendModule;
use Contao\System;

/**
 * The bridge Contao needs for a menu item without a table: a `callback`
 * class built with `new`, outside the container — it only hands over to the
 * service, as in the Consho core.
 */
class LicenseModule extends BackendModule
{
    public function generate(): string
    {
        /** @var LicenseBackendController $controller */
        $controller = System::getContainer()->get(LicenseBackendController::class);

        return $controller();
    }

    protected function compile(): void
    {
    }
}
