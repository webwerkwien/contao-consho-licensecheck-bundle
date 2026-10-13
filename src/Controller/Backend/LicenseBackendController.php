<?php declare(strict_types=1);

namespace Webwerkwien\ContaoConshoLicensecheckBundle\Controller\Backend;

use Contao\CoreBundle\Exception\RedirectResponseException;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\Message;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Twig\Environment;
use Webwerkwien\ContaoConshoLicensecheckBundle\License\LicenseEntry;
use Webwerkwien\ContaoConshoLicensecheckBundle\License\LicenseFetcher;
use Webwerkwien\ContaoConshoLicensecheckBundle\License\LicenseStatus;
use Webwerkwien\ContaoConshoLicensecheckBundle\Product\Products;
use Webwerkwien\ContaoConshoLicensecheckBundle\Store\Installation;

/**
 * "System → Lizenzen" (block licence, 4.5): every paid extension with its
 * state, one field to enter a key (the key says which extension it is
 * for), "Jetzt prüfen" and "Installation freigeben".
 *
 * Administrators only. The key is entered here and never shown again —
 * not even to the administrator who entered it. GET renders, POST acts and
 * redirects back (POST, redirect, GET); Contao checks the request token of
 * every back end POST.
 *
 * Public, because LicenseModule fetches it from the container.
 */
#[Autoconfigure(public: true)]
final class LicenseBackendController
{
    public const TEMPLATE = '@Contao/backend/consho_licensecheck.html.twig';

    public function __construct(
        private readonly LicenseStatus $status,
        private readonly LicenseEntry $entry,
        private readonly LicenseFetcher $fetcher,
        private readonly Products $products,
        private readonly Installation $installation,
        private readonly Security $security,
        private readonly ContaoFramework $framework,
        private readonly RequestStack $requests,
        private readonly Environment $twig,
    ) {
    }

    public function __invoke(): string
    {
        if (!$this->security->isGranted('ROLE_ADMIN')) {
            throw new AccessDeniedHttpException();
        }

        $request = $this->requests->getCurrentRequest();
        $lang = $GLOBALS['TL_LANG']['CONSHO_LICENSECHECK'] ?? [];
        $message = $this->framework->getAdapter(Message::class);

        if (null !== $request && $request->isMethod('POST') && 'consho_licensecheck' === $request->request->getString('FORM_SUBMIT')) {
            $this->act($request->request->getString('action'), $request->request->getString('product'), $request->request->getString('licenseKey'), $lang, $message);

            throw new RedirectResponseException($request->getUri());
        }

        return $this->twig->render(self::TEMPLATE, [
            'messages' => (string) $message->generate(),
            'states' => $this->status->all(time()),
            'installation' => $this->installation->id(),
            'action' => null === $request ? '' : $request->getUri(),
            'lang' => $lang,
        ]);
    }

    /**
     * @param array<string, mixed> $lang
     */
    private function act(string $action, string $product, string $key, array $lang, object $message): void
    {
        $outcomes = (array) ($lang['outcome'] ?? []);

        try {
            if ('set' === $action) {
                $entered = $this->entry->enter($key, time());
                $message->addInfo(\sprintf('%s: %s', $entered['name'], (string) ($outcomes[$entered['outcome']] ?? $entered['outcome'])));

                return;
            }

            $known = $this->products->get($product);

            if (null === $known) {
                return;
            }

            if ('refresh' === $action) {
                $outcome = $this->fetcher->fetch($product, time());
                $message->addInfo(\sprintf('%s: %s', $known->name(), (string) ($outcomes[$outcome] ?? $outcome)));
            } elseif ('release' === $action) {
                $this->fetcher->release($product, time());
                $message->addConfirmation(\sprintf((string) ($lang['released'] ?? '%s released.'), $known->name()));
            }
        } catch (\InvalidArgumentException $e) {
            $message->addError($e->getMessage());
        }
    }
}
