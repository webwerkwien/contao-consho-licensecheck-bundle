<?php declare(strict_types=1);

namespace Webwerkwien\ContaoConshoLicensecheckBundle\EventListener;

use Contao\CoreBundle\DependencyInjection\Attribute\AsHook;
use Contao\StringUtil;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Webwerkwien\ContaoConshoLicensecheckBundle\License\LicenseStatus;

/**
 * The licence state on the back end start page (block licence, 4.5), for
 * administrators: an error per paid extension that is locked, a note per one
 * whose shop has not answered for seven days. Nothing in `dev` — there
 * nothing is locked (L4).
 *
 * Through Contao's own hook: the checker does not depend on the Consho core
 * and its findings.
 */
#[AsHook('getSystemMessages')]
class LicenseMessagesListener
{
    public function __construct(
        private readonly LicenseStatus $status,
        private readonly Security $security,
        #[Autowire('%kernel.environment%')]
        private readonly string $environment,
    ) {
    }

    public function __invoke(): string
    {
        if ('dev' === $this->environment || !$this->security->isGranted('ROLE_ADMIN')) {
            return '';
        }

        return $this->messages($this->status->all(time()));
    }

    /**
     * @param list<array{name: string, allowed: bool, reason: string, stale: bool}> $states
     */
    public function messages(array $states): string
    {
        $lang = $GLOBALS['TL_LANG']['CONSHO_LICENSECHECK'] ?? [];
        $errors = [];
        $notes = [];

        foreach ($states as $state) {
            if (!$state['allowed']) {
                $errors[] = \sprintf('%s: %s', $state['name'], (string) ($lang['reason'][$state['reason']] ?? $state['reason']));
            } elseif ($state['stale']) {
                $notes[] = \sprintf((string) ($lang['stale'] ?? '%s: no confirmation for 7 days.'), $state['name']);
            }
        }

        $box = static fn (string $class, array $lines): string => [] === $lines ? '' : \sprintf('<div class="%s">%s</div>', $class, implode('', array_map(static fn (string $line): string => '<p>'.StringUtil::specialchars($line).'</p>', $lines)));

        return $box('tl_error', $errors).$box('tl_info', $notes);
    }
}
