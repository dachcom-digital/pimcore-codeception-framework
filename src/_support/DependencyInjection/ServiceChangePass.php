<?php

namespace Dachcom\Codeception\DependencyInjection;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class ServiceChangePass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        $this->changeSessionMockFileClass($container);
    }

    protected function changeSessionMockFileClass(ContainerBuilder $container): void
    {
        // @todo: check if this has been resolved.
        //$container->getDefinition('session.storage.mock_file')->setClass(MockFileSessionStorage::class);
    }
}
