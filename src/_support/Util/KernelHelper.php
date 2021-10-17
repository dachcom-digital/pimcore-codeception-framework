<?php

namespace Dachcom\Codeception\Util;

use Pimcore\Config;
use Symfony\Component\ErrorHandler\Debug;
use Symfony\Component\HttpKernel\KernelInterface;

class KernelHelper
{
    public static function buildTestKernel(bool $debug, string $configuration): KernelInterface
    {
        putenv('APP_DEBUG=' . $_SERVER['APP_DEBUG'] = $_ENV['APP_DEBUG'] = ($debug ? '1' : '0'));

        if ($debug) {
            umask(0000);
            Debug::enable();
            @ini_set('display_errors', 'On');
        }

        $kernel = new \TestKernel(Config::getEnvironment(), $debug, $configuration);
        \Pimcore::setKernel($kernel);
        $kernel->boot();

        return $kernel;
    }

    public static function setLocalEnvVarsForRemoteKernel(array $localEnvVariables = []): void
    {
        $vars = '';
        foreach ($localEnvVariables as $key => $value) {
            $vars .= sprintf('%s=%s' . "\n", $key, $value);
        }

        file_put_contents(PIMCORE_PROJECT_ROOT . '/.env.test.local', $vars);
    }

    public static function removeLocalEnvVarsForRemoteKernel(): void
    {
        if (!is_file(PIMCORE_PROJECT_ROOT . '/.env.test.local')) {
            return;
        }

        unlink(PIMCORE_PROJECT_ROOT . '/.env.test.local');
    }
}
