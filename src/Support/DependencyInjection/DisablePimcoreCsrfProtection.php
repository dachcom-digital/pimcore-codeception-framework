<?php

/*
 * This source file is available under two different licenses:
 *   - GNU General Public License version 3 (GPLv3)
 *   - DACHCOM Commercial License (DCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) DACHCOM.DIGITAL AG (https://www.dachcom-digital.com)
 * @license    GPLv3 and DCL
 */

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
