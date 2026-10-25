<?php declare(strict_types=1);

namespace Webwerkwien\ContaoConshoLicensecheckBundle\Tests\License;

use Doctrine\DBAL\Connection;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\Exception\TransportException;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Webwerkwien\ContaoConshoLicensecheckBundle\License\LicenseFetcher;
use Webwerkwien\ContaoConshoLicensecheckBundle\License\LicenseKeys;
use Webwerkwien\ContaoConshoLicensecheckBundle\Product\Products;
use Webwerkwien\ContaoConshoLicensecheckBundle\Store\Installation;
use Webwerkwien\ContaoConshoLicensecheckBundle\Store\LicenseStore;
use Webwerkwien\ContaoConshoLicensecheckBundle\Tests\Fixtures\Shop;
use Webwerkwien\ContaoConshoLicensecheckBundle\Tests\Schema;

/**
 * Block licence 4.3/4.5: fetching from the address in the key — only key,
 * installation id and version are sent (L8, E1); unreachable, throttled,
 * `unknown` or unsigned keeps the last confirmation (E2, E3).
 */
class LicenseFetcherTest extends TestCase
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
        $this->store->setKey('acme-tool', $this->shop->key(self::LIC), self::NOW);
    }

    public function testHoltUndSpeichert(): void
    {
        $sent = [];
        $client = new MockHttpClient(function (string $method, string $url, array $options) use (&$sent): MockResponse {
            $sent = [$method, $url, json_decode($options['body'], true)];

            return new MockResponse($this->shop->confirmation(self::LIC, $this->ins, self::NOW));
        });

        self::assertSame('active', $this->fetcher($client)->fetch('acme-tool', self::NOW + 5));

        self::assertSame(['POST', 'https://shop.example.org/_consho/license/confirm', ['key' => $this->shop->key(self::LIC), 'ins' => $this->ins, 'ver' => '2.1.0']], $sent, 'nur Schlüssel, Kennung, Version');
        $row = $this->store->row('acme-tool');
        self::assertIsArray($row);
        self::assertSame([self::NOW + 5, self::NOW, ''], [(int) $row['fetchedAt'], (int) $row['seenCheckedAt'], $row['error']]);
    }

    /**
     * @return iterable<string, array{0: callable(Shop, string): MockResponse, 1: string}>
     */
    public static function nichtsNeues(): iterable
    {
        yield 'gedrosselt' => [static fn (Shop $s, string $ins): MockResponse => new MockResponse('', ['http_code' => 429]), 'throttled'];
        yield 'Serverfehler' => [static fn (Shop $s, string $ins): MockResponse => new MockResponse('', ['http_code' => 503]), 'unreachable'];
        yield 'nicht erreichbar' => [static fn (Shop $s, string $ins): MockResponse => new MockResponse('', ['error' => 'host unreachable']), 'unreachable'];
        yield 'unknown (älteres Backup)' => [static fn (Shop $s, string $ins): MockResponse => new MockResponse($s->confirmation(self::LIC, $ins, self::NOW + 60, ['state' => 'unknown'])), 'unknown'];
        yield 'fremd signiert' => [static fn (Shop $s, string $ins): MockResponse => new MockResponse((new Shop())->confirmation(self::LIC, $ins, self::NOW + 60, ['state' => 'revoked'])), 'invalidAnswer'];
        yield 'für eine andere Installation' => [static fn (Shop $s, string $ins): MockResponse => new MockResponse($s->confirmation(self::LIC, str_repeat('f', 32), self::NOW + 60, ['state' => 'revoked'])), 'invalidAnswer'];
        yield 'Unsinn' => [static fn (Shop $s, string $ins): MockResponse => new MockResponse('<html>Wartung</html>'), 'invalidAnswer'];
    }

    /**
     * @param callable(Shop, string): MockResponse $answer
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('nichtsNeues')]
    public function testBehaeltDieLetzteBestaetigung(callable $answer, string $error): void
    {
        $first = $this->shop->confirmation(self::LIC, $this->ins, self::NOW);
        $this->store->saveConfirmation('acme-tool', $first, self::NOW, self::NOW);

        self::assertSame($error, $this->fetcher(new MockHttpClient($answer($this->shop, $this->ins)))->fetch('acme-tool', self::NOW + 60));

        $row = $this->store->row('acme-tool');
        self::assertIsArray($row);
        self::assertSame([$first, self::NOW, self::NOW + 60, $error], [$row['confirmation'], (int) $row['fetchedAt'], (int) $row['attemptedAt'], $row['error']]);
    }

    public function testEinGesperrterZustandWirdGespeichert(): void
    {
        $client = new MockHttpClient(new MockResponse($this->shop->confirmation(self::LIC, $this->ins, self::NOW, ['state' => 'revoked'])));

        self::assertSame('revoked', $this->fetcher($client)->fetch('acme-tool', self::NOW));
        self::assertStringStartsWith('CONSHOC1.', (string) $this->store->row('acme-tool')['confirmation']);
    }

    public function testFreigebenSchicktDieBitteUndVergisstDenSchluessel(): void
    {
        $sent = null;
        $client = new MockHttpClient(function (string $method, string $url, array $options) use (&$sent): MockResponse {
            $sent = json_decode($options['body'], true);

            return new MockResponse('', ['error' => 'host unreachable']);
        });

        $this->fetcher($client)->release('acme-tool', self::NOW);

        self::assertTrue($sent['release'] ?? false);
        self::assertNull($this->store->row('acme-tool'), 'auch wenn der Shop nicht erreichbar ist');
    }

    public function testOhneSchluesselKeineAnfrage(): void
    {
        $this->store->forget('acme-tool');
        $client = new MockHttpClient(static fn (): MockResponse => throw new TransportException('darf nicht gefragt werden'));

        self::assertSame('noKey', $this->fetcher($client)->fetch('acme-tool', self::NOW));
    }

    private function fetcher(MockHttpClient $client): LicenseFetcher
    {
        $products = new Products([$this->shop->product()]);

        return new LicenseFetcher($products, $this->store, new Installation($this->connection), new LicenseKeys($products), $client);
    }
}
