<?php

namespace Dachcom\Codeception\DependencyInjection;

use Dachcom\Codeception\App\Session\MockFileSessionStorage;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class ServiceChangePass implements CompilerPassInterface
{
    /**
     * @param ContainerBuilder $container
     */
    public function process(ContainerBuilder $container)
    {
        $this->changeSessionMockFileClass($container);
    }

    /**
     * @param ContainerBuilder $container
     */
    protected function changeSessionMockFileClass(ContainerBuilder $container)
    {
        $container->getDefinition('session.storage.mock_file')->setClass(MockFileSessionStorage::class);
    }
}
