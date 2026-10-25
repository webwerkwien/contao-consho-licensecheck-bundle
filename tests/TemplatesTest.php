<?php declare(strict_types=1);

namespace Webwerkwien\ContaoConshoLicensecheckBundle\Tests;

use PHPUnit\Framework\TestCase;
use Webwerkwien\ContaoConshoLicensecheckBundle\Controller\Backend\LicenseBackendController;

/**
 * Learned in the licensing package (LZ-5): without `.twig-root` Contao takes
 * a subfolder for a grouping, not for part of the name, and "@Contao/
 * backend/…" is "not defined". No unit test renders through Contao.
 */
class TemplatesTest extends TestCase
{
    private const ROOT = __DIR__.'/../contao/templates';

    public function testVorlagenOrdnerIstTwigRoot(): void
    {
        self::assertFileExists(self::ROOT.'/.twig-root');
    }

    public function testJedeGenannteVorlageLiegtUnterIhremNamen(): void
    {
        self::assertFileExists(self::ROOT.'/'.substr(LicenseBackendController::TEMPLATE, \strlen('@Contao/')));
    }

    /**
     * The page never shows the key — not even to whoever entered it.
     */
    public function testDieSeiteKenntKeinenSchluessel(): void
    {
        $template = (string) file_get_contents(self::ROOT.'/backend/consho_licensecheck.html.twig');

        self::assertStringNotContainsString('licenseKey }}', $template);
        self::assertStringNotContainsString('state.key', $template);
    }
}
