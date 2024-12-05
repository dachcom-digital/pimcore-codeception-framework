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
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;

class MakeServicesPublicPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        $prefix = getenv('TEST_BUNDLE_NAME');
        $serviceIds = array_filter($container->getServiceIds(), static function (string $id) use ($prefix) {
            return str_starts_with($id, $prefix);
        });

        foreach ($serviceIds as $serviceId) {
            if ($container->hasAlias($serviceId)) {
                $container->getAlias($serviceId)->setPublic(true);
            }

            try {
                $definition = $container->findDefinition($serviceId);
            } catch (ServiceNotFoundException $e) {
                // fails silently.
                continue;
            }

            $definition->setPublic(true);
        }
    }
}
