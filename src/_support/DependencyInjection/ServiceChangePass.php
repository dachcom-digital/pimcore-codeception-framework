<?php

namespace Dachcom\Codeception\DependencyInjection;

use Pimcore;
use Dachcom\Codeception\App\Session\MockFileSessionStorage;
use Dachcom\Codeception\App\Pimcore\TestConfig;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

class ServiceChangePass implements CompilerPassInterface
{
    /**
     * @param ContainerBuilder $container
     */
    public function process(ContainerBuilder $container)
    {
        $this->changeSessionMockFileClass($container);
        $this->changeConfigClass($container);
    }

    /**
     * @param ContainerBuilder $container
     */
    protected function changeSessionMockFileClass(ContainerBuilder $container)
    {
        $container->getDefinition('session.storage.mock_file')->setClass(MockFileSessionStorage::class);
    }

    /**
     * @param ContainerBuilder $container
     */
    protected function changeConfigClass(ContainerBuilder $container)
    {
        if (!$container->hasDefinition('Pimcore\Config')) {
            return;
        }

        $testService = new Definition(TestConfig::class);
        $testService->setPublic(true);

        $container->setDefinition(TestConfig::class, $testService);
        $container->getDefinition(Pimcore\Config::class)->setClass(TestConfig::class);
    }
}
