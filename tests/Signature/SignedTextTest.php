<?php declare(strict_types=1);

namespace Webwerkwien\ContaoConshoLicensecheckBundle\Tests\Signature;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Webwerkwien\ContaoConshoLicensecheckBundle\Signature\SignedText;
use Webwerkwien\ContaoConshoLicensecheckBundle\Tests\Fixtures\Shop;

/**
 * Block licence 4.3: whatever does not match the format exactly gives null —
 * never an exception, never a result (LZ-7). Tested on the reader itself, not
 * only through the gate, which let four mutations of the reader pass.
 */
class SignedTextTest extends TestCase
{
    private static Shop $first;

    private static Shop $second;

    public static function setUpBeforeClass(): void
    {
        self::$first = new Shop();
        self::$second = new Shop();
    }

    public function testDerZweiteSchluesselWirdUeberSeineKennungGefunden(): void
    {
        $text = self::$second->sign('CONSHOC1', ['v' => 1, 'x' => 'y']);

        self::assertSame('y', SignedText::read('CONSHOC1', $text, self::keys())['x'] ?? null);
    }

    /**
     * A line break copied along with the shop's public key must not void
     * every licence. base64_decode() skips whitespace even in strict mode;
     * this holds it to that.
     */
    public function testLeerraumAmKonfiguriertenSchluesselSchadetNicht(): void
    {
        $text = self::$first->sign('CONSHOC1', ['v' => 1, 'x' => 'y']);

        self::assertSame('y', SignedText::read('CONSHOC1', $text, [self::$first->kid => ' '.base64_encode(self::$first->public)."\n"])['x'] ?? null);
    }

    /**
     * @return iterable<string, array{0: callable(): string}>
     */
    public static function kaputt(): iterable
    {
        $valid = static fn (): string => self::$first->sign('CONSHOC1', ['v' => 1]);

        yield 'ein vierter Teil' => [static fn (): string => $valid().'.AAAA'];
        yield 'nur zwei Teile' => [static fn (): string => implode('.', \array_slice(explode('.', $valid()), 0, 2))];
        yield 'andere Art' => [static fn (): string => self::$first->sign('CONSHO1', ['v' => 1])];
        yield 'Signatur gekürzt' => [static function () use ($valid): string {
            [$kind, $json] = explode('.', $valid());

            return $kind.'.'.$json.'.'.rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');
        }];
        yield 'Signatur mit Padding' => [static fn (): string => $valid().'=='];
        yield 'kein JSON' => [static function (): string {
            $json = rtrim(strtr(base64_encode('kein json'), '+/', '-_'), '=');

            return 'CONSHOC1.'.$json.'.'.explode('.', self::$first->sign('CONSHOC1', ['v' => 1]))[2];
        }];
        yield 'ohne kid' => [static fn (): string => self::$first->sign('CONSHOC1', ['v' => 1], '')];
        yield 'unbekannte kid' => [static fn (): string => self::$first->sign('CONSHOC1', ['v' => 1], str_repeat('0', 16))];
        yield 'kid des anderen Schlüssels' => [static fn (): string => self::$first->sign('CONSHOC1', ['v' => 1], self::$second->kid)];
        yield 'leer' => [static fn (): string => ''];
        yield 'Unsinn' => [static fn (): string => 'CONSHOC1.!!!.???'];
    }

    #[DataProvider('kaputt')]
    public function testKaputtErgibtNull(callable $text): void
    {
        self::assertNull(SignedText::read('CONSHOC1', $text(), self::keys()));
    }

    /**
     * @return array<string, string>
     */
    private static function keys(): array
    {
        return [
            self::$first->kid => base64_encode(self::$first->public),
            self::$second->kid => base64_encode(self::$second->public),
        ];
    }
}
