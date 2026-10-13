<?php declare(strict_types=1);

namespace Webwerkwien\ContaoConshoLicensecheckBundle;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

/**
 * The licence check in the customer's installation (block licence, 4.5; L18):
 * one checker for every paid extension installed, each with its own key and
 * its own daily confirmation (L19).
 *
 * Nothing to configure. Which public keys a product trusts comes from the
 * product's own code (P1), never from the installation's configuration — a
 * customer could otherwise trust a key pair of his own.
 */
class ContaoConshoLicensecheckBundle extends AbstractBundle
{
    public function loadExtension(array $config, ContainerConfigurator $containerConfigurator, ContainerBuilder $containerBuilder): void
    {
        $containerConfigurator->import('../config/services.yaml');
    }
}
