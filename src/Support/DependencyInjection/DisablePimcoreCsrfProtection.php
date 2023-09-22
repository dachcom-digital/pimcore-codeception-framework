<?php

namespace Dachcom\Codeception\Support\DependencyInjection;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class DisablePimcoreCsrfProtection implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        codecept_debug('[Security] Disable Pimcore CsrfProtectionListener');

        $container->removeDefinition(\Pimcore\Bundle\AdminBundle\EventListener\CsrfProtectionListener::class);
    }
}
