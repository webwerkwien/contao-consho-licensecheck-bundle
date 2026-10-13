<?php declare(strict_types=1);

namespace Webwerkwien\ContaoConshoLicensecheckBundle\Tests;

use PHPUnit\Framework\TestCase;

/**
 * Seen on c5 (LZ-6): Contao loads the English file first as fallback, then
 * the user's language. The page texts were added with `+=`, which keeps
 * existing keys — the page stayed English for a German user.
 */
class LanguageTest extends TestCase
{
    protected function tearDown(): void
    {
        unset($GLOBALS['TL_LANG']);
    }

    public function testDeutschErsetztDenEnglischenRueckfall(): void
    {
        require __DIR__.'/../contao/languages/en/default.php';
        require __DIR__.'/../contao/languages/de/default.php';

        $lang = $GLOBALS['TL_LANG']['CONSHO_LICENSECHECK'];

        self::assertSame('Jetzt prüfen', $lang['refresh']);
        self::assertSame('Lizenz bestätigt.', $lang['outcome']['active']);
        self::assertSame('Die Lizenz ist abgelaufen.', $lang['reason']['expired']);
        self::assertStringStartsWith('%s: Seit 7 Tagen', $lang['stale']);
    }

    public function testBeideSprachenHabenDieselbenSchluessel(): void
    {
        require __DIR__.'/../contao/languages/en/default.php';
        $en = $GLOBALS['TL_LANG']['CONSHO_LICENSECHECK'];
        unset($GLOBALS['TL_LANG']);
        require __DIR__.'/../contao/languages/de/default.php';
        $de = $GLOBALS['TL_LANG']['CONSHO_LICENSECHECK'];

        self::assertSame(array_keys($en), array_keys($de));
        self::assertSame(array_keys($en['reason']), array_keys($de['reason']));
        self::assertSame(array_keys($en['outcome']), array_keys($de['outcome']));
    }
}
