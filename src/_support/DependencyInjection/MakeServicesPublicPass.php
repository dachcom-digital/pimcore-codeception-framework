<?php

namespace Dachcom\Codeception\DependencyInjection;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

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

            $container
                ->findDefinition($serviceId)
                ->setPublic(true);
        }
    }
}
