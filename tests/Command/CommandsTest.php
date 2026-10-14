<?php declare(strict_types=1);

namespace Webwerkwien\ContaoConshoLicensecheckBundle\Tests\Command;

use Doctrine\DBAL\Connection;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Webwerkwien\ContaoAiCoreBundle\Attribute\AiContract;
use Webwerkwien\ContaoConshoLicensecheckBundle\Command\RefreshCommand;
use Webwerkwien\ContaoConshoLicensecheckBundle\Command\ReleaseCommand;
use Webwerkwien\ContaoConshoLicensecheckBundle\Command\SetKeyCommand;
use Webwerkwien\ContaoConshoLicensecheckBundle\Command\StatusCommand;
use Webwerkwien\ContaoConshoLicensecheckBundle\Console\SecretInput;
use Webwerkwien\ContaoConshoLicensecheckBundle\Cron\LicenseCron;
use Webwerkwien\ContaoConshoLicensecheckBundle\EventListener\LicenseMessagesListener;
use Webwerkwien\ContaoConshoLicensecheckBundle\License\LicenseEntry;
use Webwerkwien\ContaoConshoLicensecheckBundle\License\LicenseFetcher;
use Webwerkwien\ContaoConshoLicensecheckBundle\License\LicenseGate;
use Webwerkwien\ContaoConshoLicensecheckBundle\License\LicenseKeys;
use Webwerkwien\ContaoConshoLicensecheckBundle\License\LicenseStatus;
use Webwerkwien\ContaoConshoLicensecheckBundle\Product\Products;
use Webwerkwien\ContaoConshoLicensecheckBundle\Store\Installation;
use Webwerkwien\ContaoConshoLicensecheckBundle\Store\LicenseStore;
use Webwerkwien\ContaoConshoLicensecheckBundle\Tests\Fixtures\Shop;
use Webwerkwien\ContaoConshoLicensecheckBundle\Tests\Schema;

/**
 * Block Lizenz 4.5 und L7: eintragen verdeckt und sofort bestätigt, falsch
 * wird nicht gespeichert; Zustand ohne Schlüssel; Cron in der eigenen Stunde;
 * Startseite nur bei Sperre oder 7 Tagen ohne Antwort.
 */
class CommandsTest extends TestCase
{
    private const LIC = 'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa';

    private Connection $connection;

    private Shop $shop;

    private Products $products;

    private LicenseStore $store;

    /**
     * @var list<array<string, mixed>>
     */
    private array $sent = [];

    protected function setUp(): void
    {
        $this->connection = Schema::connection();
        $this->shop = new Shop();
        $this->products = new Products([$this->shop->product()]);
        $this->store = new LicenseStore($this->connection);
    }

    protected function tearDown(): void
    {
        unset($GLOBALS['TL_LANG']);
    }

    public function testEintragenVerdecktUndSofortBestaetigt(): void
    {
        $tester = $this->set($this->shop->key(self::LIC)."\n");

        self::assertSame(['status' => 'set', 'product' => 'acme-tool', 'name' => 'Acme Tool', 'outcome' => 'active'], json_decode($tester->getDisplay(), true));
        self::assertStringNotContainsString('CONSHO1.', $tester->getDisplay(), 'der Schlüssel nie in der Ausgabe');
        self::assertCount(1, $this->sent);
    }

    public function testEinFalscherSchluesselWirdNichtGespeichert(): void
    {
        foreach ([(new Shop())->key(self::LIC), $this->shop->key(self::LIC, 'anderes-produkt'), 'Unsinn'] as $wrong) {
            try {
                $this->set($wrong."\n");
                self::fail('abgelehnt erwartet');
            } catch (\InvalidArgumentException $e) {
                self::assertStringContainsString('not a licence key', $e->getMessage());
            }
        }

        self::assertNull($this->store->row('acme-tool'));
        self::assertSame([], $this->sent, 'kein Netz für einen falschen Schlüssel');
    }

    public function testNieAusDemWebProzess(): void
    {
        $this->expectExceptionMessage('console only');
        $tester = new CommandTester(new SetKeyCommand($this->entry(), new SecretInput('fpm-fcgi')));
        $tester->setInputs([$this->shop->key(self::LIC)]);
        $tester->execute([], ['interactive' => false]);
    }

    public function testZustandOhneSchluessel(): void
    {
        $this->set($this->shop->key(self::LIC)."\n");
        $tester = new CommandTester(new StatusCommand($this->licenseStatus(), new Installation($this->connection)));
        $tester->execute([]);
        $status = json_decode($tester->getDisplay(), true);

        self::assertSame([self::LIC, true, 'active'], [$status['licenses'][0]['licenseId'], $status['licenses'][0]['allowed'], $status['licenses'][0]['reason']]);
        self::assertMatchesRegularExpression('/^[0-9a-f]{32}$/', $status['installation']);
        self::assertStringNotContainsString('CONSHO1.', $tester->getDisplay());
    }

    public function testJetztPruefenUndFreigeben(): void
    {
        $this->set($this->shop->key(self::LIC)."\n");

        $refresh = new CommandTester(new RefreshCommand($this->products, $this->fetcher()));
        $refresh->execute([]);
        self::assertSame(['outcomes' => ['acme-tool' => 'active']], json_decode($refresh->getDisplay(), true));

        $release = new CommandTester(new ReleaseCommand($this->products, $this->fetcher()));
        $release->execute(['product' => 'acme-tool']);
        self::assertSame('released', json_decode($release->getDisplay(), true)['status']);
        self::assertTrue(end($this->sent)['release'] ?? false);
        self::assertNull($this->store->row('acme-tool'));

        $this->expectExceptionMessage('No paid extension "fremd"');
        (new CommandTester(new RefreshCommand($this->products, $this->fetcher())))->execute(['product' => 'fremd']);
    }

    public function testCronInDerEigenenStunde(): void
    {
        $ins = (new Installation($this->connection))->id();
        $slot = LicenseCron::slotOf($ins);
        $day = 86400 * 1000;
        $inSlot = $day + $slot * 3600;
        $notSlot = $day + (($slot + 1) % 24) * 3600;

        self::assertTrue(LicenseCron::due(0, $slot, $inSlot), 'nie geholt');
        self::assertFalse(LicenseCron::due($inSlot - 19 * 3600, $slot, $inSlot), 'jünger als 20 Stunden');
        self::assertTrue(LicenseCron::due($inSlot - 21 * 3600, $slot, $inSlot), 'älter als 20 Stunden, eigene Stunde');
        self::assertFalse(LicenseCron::due($notSlot - 21 * 3600, $slot, $notSlot), 'älter als 20 Stunden, fremde Stunde');
        self::assertTrue(LicenseCron::due($notSlot - 27 * 3600, $slot, $notSlot), 'älter als 26 Stunden: sofort');

        $this->set($this->shop->key(self::LIC)."\n");
        $this->sent = [];
        $cron = new LicenseCron($this->products, $this->store, new Installation($this->connection), $this->fetcher());
        self::assertSame([], $cron->run(time() + 3600), 'eben erst geholt');
        self::assertSame(['acme-tool'], $cron->run(time() + 27 * 3600));
    }

    /**
     * Gefunden im Review (LZ-7): Ein Fehler beim ersten Produkt (etwa aus der
     * Datenbank) ließ die übrigen nicht mehr fragen. Jetzt fragen alle, und
     * der Fehler kommt danach, damit Contao ihn protokolliert.
     */
    public function testEinFehlerHaeltDieAnderenNichtAuf(): void
    {
        $other = new Shop();
        $products = new Products([$this->shop->product('a-tool'), $other->product('b-tool')]);
        $this->store->setKey('a-tool', $this->shop->key(self::LIC, 'a-tool'), 0);
        $this->store->setKey('b-tool', $other->key(self::LIC, 'b-tool'), 0);

        $asked = [];
        $fetcher = $this->createMock(LicenseFetcher::class);
        $fetcher->method('fetch')->willReturnCallback(static function (string $product) use (&$asked): string {
            $asked[] = $product;

            if ('a-tool' === $product) {
                throw new \RuntimeException('Datenbank weg');
            }

            return 'active';
        });

        try {
            (new LicenseCron($products, $this->store, new Installation($this->connection), $fetcher))->run(time());
            self::fail('Fehler erwartet');
        } catch (\RuntimeException $e) {
            self::assertSame('Datenbank weg', $e->getMessage());
        }

        self::assertSame(['a-tool', 'b-tool'], $asked);
    }

    public function testStartseiteNurBeiSperreOderSiebenTagen(): void
    {
        require __DIR__.'/../../contao/languages/de/default.php';
        $listener = new LicenseMessagesListener($this->licenseStatus(), $this->createMock(\Symfony\Bundle\SecurityBundle\Security::class), 'prod');

        self::assertSame('', $listener->messages([['name' => 'Acme Tool', 'allowed' => true, 'reason' => 'active', 'stale' => false]]));
        self::assertSame('<div class="tl_error"><p>Acme Tool: Es ist kein Lizenzschlüssel eingetragen. Unter System → Lizenzen eintragen.</p></div>', $listener->messages([['name' => 'Acme Tool', 'allowed' => false, 'reason' => 'noKey', 'stale' => false]]));
        self::assertStringStartsWith('<div class="tl_info"><p>Acme Tool: Seit 7 Tagen', $listener->messages([['name' => 'Acme Tool', 'allowed' => true, 'reason' => 'active', 'stale' => true]]));
        self::assertSame('', (new LicenseMessagesListener($this->licenseStatus(), $this->createMock(\Symfony\Bundle\SecurityBundle\Security::class), 'dev'))(), 'in dev nichts');
    }

    public function testVertraege(): void
    {
        $contract = static fn (string $class): AiContract => (new \ReflectionClass($class))->getAttributes(AiContract::class)[0]->newInstance();

        self::assertFalse($contract(StatusCommand::class)->writes);
        self::assertNotNull($contract(ReleaseCommand::class)->irreversible);
        self::assertTrue($contract(SetKeyCommand::class)->writes);

        // Gelesen wie contao-ai sie liest: ein Vertrag mit Mängeln wird dort
        // gekürzt angezeigt — „writes ohne trace" stand bis 2026-10-14 an allen
        // drei schreibenden Befehlen (CLI-Abdeckung, Abnahme).
        foreach (glob(__DIR__.'/../../src/Command/*Command.php') ?: [] as $file) {
            $class = 'Webwerkwien\\ContaoConshoLicensecheckBundle\\Command\\'.basename($file, '.php');
            $read = \Webwerkwien\ContaoAiCoreBundle\Contract\ContractReader::read($class);

            self::assertIsArray($read, $class);
            self::assertSame([], $read['problems'], $class);
        }
    }

    private function set(string $stdin): CommandTester
    {
        $tester = new CommandTester(new SetKeyCommand($this->entry(), new SecretInput('cli')));
        $tester->setInputs([$stdin]);
        $tester->execute([], ['interactive' => false]);

        return $tester;
    }

    private function entry(): LicenseEntry
    {
        return new LicenseEntry(new LicenseKeys($this->products), $this->store, $this->fetcher());
    }

    private function fetcher(): LicenseFetcher
    {
        $client = new MockHttpClient(function (string $method, string $url, array $options): MockResponse {
            $body = json_decode($options['body'], true);
            $this->sent[] = $body;

            return new MockResponse($this->shop->confirmation(self::LIC, $body['ins'], time()));
        });

        return new LicenseFetcher($this->products, $this->store, new Installation($this->connection), new LicenseKeys($this->products), $client);
    }

    private function licenseStatus(): LicenseStatus
    {
        $gate = new LicenseGate($this->products, $this->store, new Installation($this->connection), new LicenseKeys($this->products), 'prod');

        return new LicenseStatus($this->products, $this->store, $gate);
    }
}
