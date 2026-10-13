<?php declare(strict_types=1);

namespace Webwerkwien\ContaoConshoLicensecheckBundle\ContaoManager;

use Contao\CoreBundle\ContaoCoreBundle;
use Contao\ManagerPlugin\Bundle\BundlePluginInterface;
use Contao\ManagerPlugin\Bundle\Config\BundleConfig;
use Contao\ManagerPlugin\Bundle\Parser\ParserInterface;
use Webwerkwien\ContaoConshoLicensecheckBundle\ContaoConshoLicensecheckBundle;

/**
 * Loaded after Contao's core only: the checker does not depend on the Consho
 * core, so any Contao extension can use it (L18).
 */
class Plugin implements BundlePluginInterface
{
    public function getBundles(ParserInterface $parser): array
    {
        return [
            BundleConfig::create(ContaoConshoLicensecheckBundle::class)
                ->setLoadAfter([ContaoCoreBundle::class]),
        ];
    }
}
