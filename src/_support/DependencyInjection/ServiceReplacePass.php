<?php

namespace Dachcom\Codeception\DependencyInjection;

use Pimcore\Bundle\AdminBundle\Session\Handler\AdminSessionHandler;
use Pimcore\Http\RequestHelper;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\TypedReference;

class ServiceReplacePass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        // @todo: remove this if https://github.com/pimcore/pimcore/issues/11927#issuecomment-1320510099 has been fixed

        $definition = $container->getDefinition(AdminSessionHandler::class);

        $aware = false;
        foreach ($definition->getArguments() as $argument) {

            if (!$argument instanceof TypedReference) {
                continue;
            }

            if ($argument->getType() === RequestHelper::class) {
                $aware = true;
                break;
            }
        }

        if ($aware === false) {
            return;
        }

        codecept_debug(sprintf(
            '[Quirks] Replacing %s with %s',
            AdminSessionHandler::class,
            \Dachcom\Codeception\App\Services\AdminSessionHandler::class
            )
        );

        $definition->setClass(\Dachcom\Codeception\App\Services\AdminSessionHandler::class);
    }
}
