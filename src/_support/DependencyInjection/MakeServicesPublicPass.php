<?php

namespace Dachcom\Codeception\DependencyInjection;

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
