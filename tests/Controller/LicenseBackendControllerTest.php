<?php declare(strict_types=1);

namespace Webwerkwien\ContaoConshoLicensecheckBundle\Tests\Controller;

use Contao\CoreBundle\Exception\RedirectResponseException;
use Contao\CoreBundle\Framework\Adapter;
use Contao\CoreBundle\Framework\ContaoFramework;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Twig\Environment;
use Twig\Loader\ArrayLoader;
use Webwerkwien\ContaoConshoLicensecheckBundle\Controller\Backend\LicenseBackendController;
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
 * „System → Lizenzen" (4.5), nachgetragen nach dem Review (LZ-7): Die Seite
 * selbst wurde von keinem Test aufgerufen. Nur Administratoren; POST nur mit
 * dem eigenen FORM_SUBMIT; ein falscher Schlüssel wird nicht gespeichert und
 * auf Deutsch abgelehnt.
 *
 * Nicht geprüft: was Contao auf ROLE_ADMIN antwortet und das Request-Token —
 * beides im echten Backend auf c5.
 */
class LicenseBackendControllerTest extends TestCase
{
    private const LIC = 'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa';

    private Connection $connection;

    private Shop $shop;

    private Products $products;

    private LicenseStore $store;

    /**
     * @var list<array{0: string, 1: string}>
     */
    private array $messages = [];

    private int $sent = 0;

    protected function setUp(): void
    {
        $this->connection = Schema::connection();
        $this->shop = new Shop();
        $this->products = new Products([$this->shop->product()]);
        $this->store = new LicenseStore($this->connection);
        require __DIR__.'/../../contao/languages/en/default.php';
        require __DIR__.'/../../contao/languages/de/default.php';
    }

    protected function tearDown(): void
    {
        unset($GLOBALS['TL_LANG']);
    }

    public function testNurFuerAdministratoren(): void
    {
        $this->expectException(AccessDeniedHttpException::class);
        $this->controller(Request::create('/contao'), admin: false)();
    }

    public function testEinFalscherSchluesselAufDeutschUndNichtGespeichert(): void
    {
        $this->post(['action' => 'set', 'licenseKey' => (new Shop())->key(self::LIC)]);

        self::assertSame([['error', 'Das ist kein Lizenzschlüssel für eine hier installierte bezahlte Erweiterung. Bitte prüfen, ob er vollständig kopiert ist.']], $this->messages);
        self::assertNull($this->store->row('acme-tool'));
        self::assertSame(0, $this->sent);
    }

    public function testEintragenUndJetztPruefen(): void
    {
        $this->post(['action' => 'set', 'licenseKey' => $this->shop->key(self::LIC)]);
        $this->post(['action' => 'refresh', 'product' => 'acme-tool']);

        self::assertSame([['info', 'Acme Tool: Lizenz bestätigt.'], ['info', 'Acme Tool: Lizenz bestätigt.']], $this->messages);
        self::assertSame(2, $this->sent);
    }

    public function testFremdesFormularTutNichts(): void
    {
        $request = Request::create('/contao', 'POST', ['FORM_SUBMIT' => 'anderes', 'action' => 'set', 'licenseKey' => $this->shop->key(self::LIC)]);

        self::assertSame('page', $this->controller($request)());
        self::assertNull($this->store->row('acme-tool'));
    }

    /**
     * @param array<string, string> $fields
     */
    private function post(array $fields): void
    {
        try {
            $this->controller(Request::create('/contao?do=consho_licensecheck', 'POST', ['FORM_SUBMIT' => 'consho_licensecheck'] + $fields))();
            self::fail('Weiterleitung erwartet (POST, redirect, GET)');
        } catch (RedirectResponseException $e) {
            self::assertStringContainsString('do=consho_licensecheck', (string) $e->getResponse()->headers->get('Location'));
        }
    }

    private function controller(Request $request, bool $admin = true): LicenseBackendController
    {
        $security = $this->createMock(Security::class);
        $security->method('isGranted')->willReturnCallback(static fn (string $role): bool => 'ROLE_ADMIN' === $role && $admin);

        $message = $this->getMockBuilder(Adapter::class)->disableOriginalConstructor()->addMethods(['addError', 'addInfo', 'addConfirmation', 'generate'])->getMock();

        foreach (['addError' => 'error', 'addInfo' => 'info', 'addConfirmation' => 'confirmation'] as $method => $type) {
            $message->method($method)->willReturnCallback(function (string $text) use ($type): void {
                $this->messages[] = [$type, $text];
            });
        }

        $framework = $this->createMock(ContaoFramework::class);
        $framework->method('getAdapter')->willReturn($message);

        $requests = new RequestStack();
        $requests->push($request);

        $installation = new Installation($this->connection);
        $keys = new LicenseKeys($this->products);
        $client = new MockHttpClient(function (string $method, string $url, array $options): MockResponse {
            ++$this->sent;

            return new MockResponse($this->shop->confirmation(self::LIC, json_decode($options['body'], true)['ins'], time()));
        });
        $fetcher = new LicenseFetcher($this->products, $this->store, $installation, $keys, $client);
        $gate = new LicenseGate($this->products, $this->store, $installation, $keys, 'prod');

        return new LicenseBackendController(
            new LicenseStatus($this->products, $this->store, $gate),
            new LicenseEntry($keys, $this->store, $fetcher),
            $fetcher,
            $this->products,
            $installation,
            $security,
            $framework,
            $requests,
            new Environment(new ArrayLoader([LicenseBackendController::TEMPLATE => 'page'])),
        );
    }
}
