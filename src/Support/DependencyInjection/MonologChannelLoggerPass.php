<?php

namespace Dachcom\Codeception\Support\DependencyInjection;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class MonologChannelLoggerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        $channelsToHide = [
            'event',
            'doctrine',
            'console',
            'cache',
            'pimcore'
        ];

        $monologHandlers = $container->getParameter('monolog.handlers_to_channels');
        foreach ($channelsToHide as $channelToHide) {
            $monologHandlers['monolog.handler.console']['elements'][] = $channelToHide;
        }

        $container->setParameter('monolog.handlers_to_channels', $monologHandlers);
    }
}
