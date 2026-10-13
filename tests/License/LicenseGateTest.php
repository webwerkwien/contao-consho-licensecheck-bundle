<?php declare(strict_types=1);

namespace Webwerkwien\ContaoConshoLicensecheckBundle\Tests\License;

use Doctrine\DBAL\Connection;
use PHPUnit\Framework\TestCase;
use Webwerkwien\ContaoConshoLicensecheckBundle\License\LicenseGate;
use Webwerkwien\ContaoConshoLicensecheckBundle\License\LicenseKeys;
use Webwerkwien\ContaoConshoLicensecheckBundle\Product\Products;
use Webwerkwien\ContaoConshoLicensecheckBundle\Store\Installation;
use Webwerkwien\ContaoConshoLicensecheckBundle\Store\LicenseStore;
use Webwerkwien\ContaoConshoLicensecheckBundle\Tests\Fixtures\Shop;
use Webwerkwien\ContaoConshoLicensecheckBundle\Tests\Schema;

/**
 * Block Lizenz 4.5: die Sperre an einer Stelle, offline, bei jedem Aufruf neu
 * geprüft — signiert, eigener Schlüssel, eigene Installation, `active`, Frist
 * nicht vorbei, Uhr nicht zurückgestellt. In `dev` frei (L4).
 */
class LicenseGateTest extends TestCase
{
    private const NOW = 2_000_000;

    private const LIC = 'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa';

    private Connection $connection;

    private Shop $shop;

    private LicenseStore $store;

    private string $ins;

    protected function setUp(): void
    {
        $this->connection = Schema::connection();
        $this->shop = new Shop();
        $this->store = new LicenseStore($this->connection);
        $this->ins = (new Installation($this->connection))->id();
    }

    protected function tearDown(): void
    {
        unset($GLOBALS['TL_LANG']);
    }

    public function testFreiMitGueltigerBestaetigung(): void
    {
        $this->confirmed();

        $verdict = $this->gate()->verdict('acme-tool', self::NOW + 86400);

        self::assertTrue($verdict->allowed);
        self::assertSame(['active', self::NOW + 14 * 86400], [$verdict->reason, $verdict->proofUntil]);
    }

    public function testInDevAllesFrei(): void
    {
        self::assertSame([true, 'dev'], [$this->gate('dev')->verdict('acme-tool', self::NOW)->allowed, $this->gate('dev')->verdict('acme-tool', self::NOW)->reason]);
    }

    public function testOhneSchluesselOderBestaetigung(): void
    {
        self::assertSame('noKey', $this->reason());

        $this->store->setKey('acme-tool', $this->shop->key(self::LIC), self::NOW);
        self::assertSame('notConfirmed', $this->reason());

        self::assertSame('unknownProduct', $this->gate()->verdict('fremd', self::NOW)->reason);
    }

    public function testDieFristLaeuftAb(): void
    {
        $this->confirmed();

        self::assertTrue($this->gate()->verdict('acme-tool', self::NOW + 14 * 86400)->allowed, 'bis einschließlich proofUntil');
        self::assertSame('proofExpired', $this->reason(self::NOW + 14 * 86400 + 1));
    }

    /**
     * @return iterable<string, array{0: string}>
     */
    public static function zustaende(): iterable
    {
        foreach (['resting', 'revoked', 'expired', 'taken'] as $state) {
            yield $state => [$state];
        }
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('zustaende')]
    public function testNurActiveIstFrei(string $state): void
    {
        $this->confirmed(['state' => $state]);

        self::assertSame($state, $this->reason());
    }

    public function testZurueckgestellteUhr(): void
    {
        $this->confirmed();

        self::assertTrue($this->gate()->verdict('acme-tool', self::NOW - 86400)->allowed, 'ein Tag Spielraum');
        self::assertSame('clock', $this->reason(self::NOW - 86400 - 1));
    }

    public function testDieUhrGiltAuchGegenFruehereBestaetigungen(): void
    {
        $this->confirmed(checkedAt: self::NOW + 10 * 86400);
        $this->confirmed(checkedAt: self::NOW, keepKey: true);

        self::assertSame('clock', $this->reason(self::NOW), 'die höchste je gesehene zählt');
    }

    /**
     * Was in der Datenbank steht, wird nicht geglaubt.
     *
     * @return iterable<string, array{0: callable(Shop, string): string}>
     */
    public static function falscheBestaetigungen(): iterable
    {
        yield 'fremde Installation' => [static fn (Shop $s, string $ins): string => $s->confirmation(self::LIC, str_repeat('f', 32), self::NOW)];
        yield 'fremde Lizenz' => [static fn (Shop $s, string $ins): string => $s->confirmation(str_repeat('b', 32), $ins, self::NOW)];
        yield 'fremdes Paar' => [static fn (Shop $s, string $ins): string => (new Shop())->confirmation(self::LIC, $ins, self::NOW)];
        yield 'fremdes Paar mit unserer Kennung' => [static fn (Shop $s, string $ins): string => (new Shop())->sign('CONSHOC1', ['v' => 1, 'lic' => self::LIC, 'ins' => $ins, 'state' => 'active', 'activeUntil' => null, 'checkedAt' => self::NOW, 'proofUntil' => self::NOW + 99_999_999], $s->kid)];
        yield 'ein Schlüssel statt Bestätigung' => [static fn (Shop $s, string $ins): string => $s->key(self::LIC)];
        yield 'von Hand verlängert' => [static function (Shop $s, string $ins): string {
            [$kind, $json, $signature] = explode('.', $s->confirmation(self::LIC, $ins, self::NOW));
            $payload = json_decode((string) base64_decode(strtr($json, '-_', '+/')), true);
            $payload['proofUntil'] = self::NOW + 999 * 86400;

            return $kind.'.'.rtrim(strtr(base64_encode((string) json_encode($payload)), '+/', '-_'), '=').'.'.$signature;
        }];
    }

    /**
     * @param callable(Shop, string): string $confirmation
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('falscheBestaetigungen')]
    public function testFalscheBestaetigungOeffnetNichts(callable $confirmation): void
    {
        $this->store->setKey('acme-tool', $this->shop->key(self::LIC), self::NOW);
        $this->store->saveConfirmation('acme-tool', $confirmation($this->shop, $this->ins), self::NOW, self::NOW);

        self::assertSame('invalidConfirmation', $this->reason());
    }

    public function testEinSchluesselFuerEinAnderesProduktGiltNicht(): void
    {
        $this->store->setKey('acme-tool', $this->shop->key(self::LIC, 'anderes'), self::NOW);

        self::assertSame('invalidKey', $this->reason());
    }

    /**
     * Gefunden durch Gegenproben: Schlüssel, Kennung und Version gehen nur
     * verschlüsselt hinaus — ein Schlüssel mit http-Adresse gilt nicht.
     */
    public function testNurHttpsAdressen(): void
    {
        $this->store->setKey('acme-tool', $this->shop->key(self::LIC, url: 'http://shop.example.org/_consho/license/confirm'), self::NOW);

        self::assertSame('invalidKey', $this->reason());
    }

    public function testDieAblehnungNenntProduktUndGrund(): void
    {
        require __DIR__.'/../../contao/languages/de/default.php';

        try {
            $this->gate()->assertAllowed('acme-tool', self::NOW);
            self::fail('gesperrt erwartet');
        } catch (\InvalidArgumentException $e) {
            self::assertSame(\InvalidArgumentException::class, $e::class, 'genau diese Klasse: contao-ai liest sie als Ablehnung');
            self::assertStringStartsWith('Acme Tool: Es ist kein Lizenzschlüssel eingetragen.', $e->getMessage());
        }

        $this->confirmed();
        $this->gate()->assertAllowed('acme-tool', self::NOW);
        $this->addToAssertionCount(1);
    }

    /**
     * Auf c5 gefunden: Auf der Konsole ist TL_LANG nicht geladen — der Satz
     * kommt über Contaos Übersetzer, der die Sprachdatei selbst lädt.
     */
    public function testDerSatzKommtUeberDenUebersetzer(): void
    {
        $translator = $this->createMock(\Symfony\Contracts\Translation\TranslatorInterface::class);
        $translator->method('trans')->willReturnCallback(static fn (string $id, array $parameters, string|null $domain): string => 'contao_default' === $domain && 'CONSHO_LICENSECHECK.reason.noKey' === $id ? 'Kein Schlüssel.' : $id);
        $products = new Products([$this->shop->product()]);
        $gate = new LicenseGate($products, $this->store, new Installation($this->connection), new LicenseKeys($products), 'prod', $translator);

        $this->expectExceptionMessage('Acme Tool: Kein Schlüssel.');
        $gate->assertAllowed('acme-tool', self::NOW);
    }

    /**
     * @param array<string, mixed> $overrides
     */
    private function confirmed(array $overrides = [], int $checkedAt = self::NOW, bool $keepKey = false): void
    {
        if (!$keepKey) {
            $this->store->setKey('acme-tool', $this->shop->key(self::LIC), self::NOW);
        }

        $this->store->saveConfirmation('acme-tool', $this->shop->confirmation(self::LIC, $this->ins, $checkedAt, $overrides), $checkedAt, $checkedAt);
    }

    private function reason(int $now = self::NOW): string
    {
        return $this->gate()->verdict('acme-tool', $now)->reason;
    }

    private function gate(string $environment = 'prod'): LicenseGate
    {
        $products = new Products([$this->shop->product()]);

        return new LicenseGate($products, $this->store, new Installation($this->connection), new LicenseKeys($products), $environment);
    }
}
