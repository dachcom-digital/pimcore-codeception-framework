<?php

namespace Dachcom\Codeception\Helper;

use Codeception\Lib\ModuleContainer;
use Codeception\TestInterface;
use Codeception\Util\Debug;
use Pimcore\Cache;
use Pimcore\Config;
use Pimcore\Event\TestEvents;
use Pimcore\Tests\Helper\Pimcore as PimcoreCoreModule;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\Kernel;

class PimcoreCore extends PimcoreCoreModule
{
    const DEFAULT_CONFIG_FILE = 'config_default.yml';

    /**
     * @var bool
     */
    protected $kernelHasCustomConfig = false;

    /**
     * @var bool
     */
    protected $kernelHasCustomSuiteConfig = false;

    /**
     * @inheritDoc
     */
    public function __construct(ModuleContainer $moduleContainer, $config = null)
    {
        $this->config = array_merge($this->config, [
            // set specific configuration file for suite
            'configuration_file' => null
        ]);

        parent::__construct($moduleContainer, $config);
    }

    /**
     * @inheritdoc
     */
    public function _initialize()
    {
        $this->setPimcoreEnvironment($this->config['environment']);
        $this->initializeKernel();
        $this->setupDbConnection();
        $this->setPimcoreCacheAvailability('disabled');
    }

    /**
     * @inheritdoc
     */
    public function _beforeSuite($settings = [])
    {
        parent::_beforeSuite($settings);

        if ($this->config['configuration_file'] === null) {
            return;
        }

        if ($this->config['configuration_file'] === self::DEFAULT_CONFIG_FILE) {
            return;
        }

        $configuration = $this->config['configuration_file'];

        $this->kernelHasCustomSuiteConfig = true;
        $this->rebootKernelWithConfiguration($configuration);
    }

    /**
     * @inheritdoc
     */
    public function _afterSuite()
    {
        parent::_afterSuite();

        if ($this->kernelHasCustomSuiteConfig !== true) {
            return;
        }

        // config has changed!
        // we need to restore default config before starting a new test!
        $this->rebootKernelWithConfiguration(null);
        $this->kernelHasCustomSuiteConfig = false;
    }

    /**
     * @inheritDoc
     */
    public function _after(TestInterface $test)
    {
        parent::_after($test);

        if ($this->kernelHasCustomConfig !== true) {
            return;
        }

        // config has changed!
        // we need to restore default config before starting a new test!
        $this->rebootKernelWithConfiguration(null);
        $this->kernelHasCustomConfig = false;

    }

    /**
     * @param string   $exception
     * @param string   $message
     * @param \Closure $callback
     */
    public function seeException($exception, $message, \Closure $callback)
    {
        $function = function () use ($callback, $exception, $message) {
            try {

                $callback();
                return false;

            } catch (\Exception $e) {

                if (get_class($e) === $exception or get_parent_class($e) === $exception) {

                    if (empty($message)) {
                        return true;
                    }

                    return $message === $e->getMessage();
                }

                return false;
            }
        };

        $this->assertTrue($function());
    }

    /**
     * Actor Function to boot symfony with a specific bundle configuration
     *
     * @param string $configuration
     * @part services
     */
    public function haveABootedSymfonyConfiguration(string $configuration)
    {
        $this->kernelHasCustomConfig = true;
        $this->rebootKernelWithConfiguration($configuration);
    }

    /**
     * @inheritdoc
     */
    protected function initializeKernel()
    {
        $maxNestingLevel = 200; // Symfony may have very long nesting level
        $xdebugMaxLevelKey = 'xdebug.max_nesting_level';
        if (ini_get($xdebugMaxLevelKey) < $maxNestingLevel) {
            ini_set($xdebugMaxLevelKey, $maxNestingLevel);
        }

        $configFile = null;
        if ($this->config['configuration_file'] !== null) {
            $configFile = $this->config['configuration_file'];
        }

        $fileSystem = new Filesystem();
        $runtimeConfigDir = codecept_data_dir() . 'config' . DIRECTORY_SEPARATOR;
        $runtimeConfigConfig = $runtimeConfigDir . DIRECTORY_SEPARATOR . 'config.yml';

        if (!$fileSystem->exists($runtimeConfigDir)) {
            $fileSystem->mkdir($runtimeConfigDir);
        }

        $clearCache = false;
        if (!$fileSystem->exists($runtimeConfigConfig)) {
            $clearCache = true;
            $fileSystem->touch($runtimeConfigConfig);
        }

        if ($clearCache === true) {
            $this->clearCache();
        }

        $this->setConfiguration($configFile);
        $this->setupPimcoreDirectories();

        $this->kernel = \Pimcore\Bootstrap::kernel();

        if ($this->config['cache_router'] === true) {
            $this->persistService('router', true);
        }

        // dispatch kernel booted event - will be used from services which need to reset state between tests
        $this->kernel->getContainer()->get('event_dispatcher')->dispatch(TestEvents::KERNEL_BOOTED);

    }

    /**
     * @param string|null $configFile
     */
    protected function rebootKernelWithConfiguration($configFile = null)
    {
        $this->setConfiguration($configFile);
        $this->getKernel()->reboot($this->getKernel()->getCacheDir());
    }

    /**
     * @param null|string $configuration
     */
    protected function setConfiguration($configuration = null)
    {
        if ($this->kernel !== null && $this->getContainer() !== null) {
            $class = $this->getContainer()->getParameter('kernel.container_class');
            $cacheDir = $this->kernel->getCacheDir();

            unlink($cacheDir . '/' . $class . '.php');
        }

        $bundleTestPath = getenv('TEST_BUNDLE_TEST_DIR');
        $bundleName = getenv('TEST_BUNDLE_NAME');

        if ($configuration === null) {
            $configuration = self::DEFAULT_CONFIG_FILE;
        }

        Debug::debug(sprintf('[%s] add custom config file %s', strtoupper($bundleName), $configuration));

        $fileSystem = new Filesystem();
        $runtimeConfigDir = codecept_data_dir() . 'config';
        $runtimeConfigDirConfig = $runtimeConfigDir . '/config.yml';

        $resource = sprintf('%s/%s/%s', $bundleTestPath, '_etc/config/bundle', $configuration);

        $fileSystem->dumpFile($runtimeConfigDirConfig, file_get_contents($resource));
    }

    /**
     * @param $env
     */
    protected function setPimcoreEnvironment($env)
    {
        Config::setEnvironment($env);
    }

    /**
     * @param string $state
     */
    protected function setPimcoreCacheAvailability($state = 'disabled')
    {
        if ($state === 'disabled') {
            Cache::disable();
        } else {
            Cache::enable();
        }
    }

    protected function clearCache()
    {
        // not required anymore in S4.
        if (Kernel::MAJOR_VERSION > 3) {
            return;
        }

        Debug::debug('[PIMCORE] Clear Cache!');

        $fileSystem = new Filesystem();
        $cacheDir = PIMCORE_SYMFONY_CACHE_DIRECTORY;

        if (!$fileSystem->exists($cacheDir)) {
            return;
        }

        $oldCacheDir = substr($cacheDir, 0, -1) . ('~' === substr($cacheDir, -1) ? '+' : '~');

        if ($fileSystem->exists($oldCacheDir)) {
            $fileSystem->remove($oldCacheDir);
        }

        $fileSystem->rename($cacheDir, $oldCacheDir);
        $fileSystem->mkdir($cacheDir);
        $fileSystem->remove($oldCacheDir);
    }

    /**
     * Override symfony internal Domains check.
     *
     * We're able to allow different hosts via pimcore sites.
     *
     * @return array
     */
    protected function getInternalDomains()
    {
        $internalDomains = [
            '/test-domain1.test/',
            '/test-domain1.test/',
            '/test-domain1.test/',
            '/test-domain2.test/',
            '/test-domain3.test/',
            '/test-domain4.test/',
            '/test-domain5.test/',
            '/test-domain6.test/',
            '/test-domain7.test/',
            '/test-domain7.test/',
            '/test-domain8.test/',
            '/www.test-domain8.test/',
        ];

        return array_unique($internalDomains);
    }
}

