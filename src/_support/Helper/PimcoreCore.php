<?php

namespace Dachcom\Codeception\Helper;

use Codeception\Lib\ModuleContainer;
use Codeception\TestInterface;
use Pimcore\Cache;
use Pimcore\Event\TestEvents;
use Pimcore\Tests\Helper\Pimcore as PimcoreCoreModule;
use Symfony\Component\EventDispatcher\GenericEvent;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\KernelInterface;

class PimcoreCore extends PimcoreCoreModule
{
    public const DEFAULT_CONFIG_FILE = 'config_default.yml';

    protected bool $dbInitialized = false;
    protected ?string $currentContainerConfiguration = null;

    public function __construct(ModuleContainer $moduleContainer, $config = null)
    {
        $this->config = array_merge($this->config, [
            // set specific configuration file for suite
            'configuration_file' => null,
            'debug'              => true
        ]);

        parent::__construct($moduleContainer, $config);
    }

    public function _initialize(): void
    {
        $this->setupTestEnvironment();
        $this->setupPimcoreDirectories();
    }

    public function _beforeSuite($settings = [])
    {
        parent::_beforeSuite($settings);

        $this->buildKernel($this->getDefaultSuiteContainerConfiguration(), $this->config['debug']);

        $this->setPimcoreCacheAvailability('disabled');
        $this->checkDatabaseState();
    }

    public function _afterSuite()
    {
        parent::_afterSuite();

        if ($this->containerDefaultsChanged() === false) {
            return;
        }

        codecept_debug('Container defaults changed _afterSuite. Rebuild Kernel...');

        $this->buildKernel($this->getDefaultSuiteContainerConfiguration(), $this->config['debug']);
    }

    public function _after(TestInterface $test): void
    {
        parent::_after($test);

        if ($this->containerDefaultsChanged() === false) {
            return;
        }

        codecept_debug('Container defaults changed _after. Rebuild Kernel...');

        $this->buildKernel($this->getDefaultSuiteContainerConfiguration(), $this->config['debug']);
    }

    protected function setupTestEnvironment()
    {
        $maxNestingLevel = 200; // Symfony may have very long nesting level
        $xdebugMaxLevelKey = 'xdebug.max_nesting_level';
        if (ini_get($xdebugMaxLevelKey) < $maxNestingLevel) {
            ini_set($xdebugMaxLevelKey, $maxNestingLevel);
        }

        $fileSystem = new Filesystem();
        $runtimeConfigDir = codecept_data_dir() . 'config' . DIRECTORY_SEPARATOR;
        $runtimeConfigConfig = $runtimeConfigDir . DIRECTORY_SEPARATOR . 'config.yml';

        if (!$fileSystem->exists($runtimeConfigDir)) {
            $fileSystem->mkdir($runtimeConfigDir);
        }

        if (!$fileSystem->exists($runtimeConfigConfig)) {
            $fileSystem->touch($runtimeConfigConfig);
        }

    }

    protected function checkDatabaseState()
    {
        if ($this->dbInitialized === true) {
            return;
        }

        $this->setupDbConnection();
        $this->dbInitialized = true;
    }

    protected function containerDefaultsChanged()
    {
        $kernelDebugState = true;
        $defaultDebugState = $this->config['debug'];

        if ($this->kernel instanceof KernelInterface) {
            $kernelDebugState = $this->kernel->isDebug();
        }

        if ($kernelDebugState !== $defaultDebugState) {
            return true;
        }

        return $this->currentContainerConfiguration !== $this->getDefaultSuiteContainerConfiguration();
    }

    protected function getDefaultSuiteContainerConfiguration()
    {
        $configuration = self::DEFAULT_CONFIG_FILE;

        if ($this->config['configuration_file'] !== null && $this->config['configuration_file'] !== self::DEFAULT_CONFIG_FILE) {
            $configuration = $this->config['configuration_file'];
        }

        return $configuration;
    }

    protected function buildKernel(string $configuration, bool $debug = true)
    {
        // nothing to do. kernel hasn't changed
        if (($this->currentContainerConfiguration === $configuration) && $this->getKernel()->isDebug() === $debug) {
            return;
        }

        if ($this->getKernel() instanceof KernelInterface) {
            $this->getKernel()->shutdown();
        }

        $this->setConfiguration($configuration);

        putenv('APP_DEBUG=' . $_SERVER['APP_DEBUG'] = $_ENV['APP_DEBUG'] = ($debug ? '1' : '0'));

        $this->kernel = \Pimcore\Bootstrap::kernel();

        if ($this->config['cache_router'] === true) {
            $this->persistService('router', true);
        }

        // dispatch kernel booted event - will be used from services which need to reset state between tests
        $this->kernel->getContainer()->get('event_dispatcher')->dispatch(new GenericEvent(), TestEvents::KERNEL_BOOTED);
    }

    protected function setConfiguration(?string $configuration = null): void
    {
        $this->currentContainerConfiguration = $configuration;

        $bundleTestPath = getenv('TEST_BUNDLE_TEST_DIR');
        $bundleName = getenv('TEST_BUNDLE_NAME');

        if ($configuration === null) {
            $configuration = self::DEFAULT_CONFIG_FILE;
        }

        codecept_debug(sprintf('[%s] add custom config file %s', strtoupper($bundleName), $configuration));

        $fileSystem = new Filesystem();
        $runtimeConfigDir = codecept_data_dir() . 'config';
        $runtimeConfigDirConfig = $runtimeConfigDir . '/config.yml';

        $resource = sprintf('%s/%s/%s', $bundleTestPath, '_etc/config/bundle', $configuration);

        $fileSystem->dumpFile($runtimeConfigDirConfig, file_get_contents($resource));

        sleep(1);
    }

    protected function setPimcoreCacheAvailability(string $state = 'disabled'): void
    {
        if ($state === 'disabled') {
            Cache::disable();
        } else {
            Cache::enable();
        }
    }

    /**
     * Override symfony internal Domains check.
     * We're able to allow different hosts via pimcore sites.
     */
    protected function getInternalDomains(): array
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

    /**
     * Actor Function to see a specific exception
     *
     * @part services
     */
    public function seeException(string $exception, ?string $message, \Closure $callback): void
    {
        $function = static function () use ($callback, $exception, $message) {
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
     * @part services
     */
    public function haveABootedSymfonyConfiguration(string $configuration, bool $debug = true): void
    {
        $kernelDebugState = true;
        if ($this->kernel instanceof KernelInterface) {
            $kernelDebugState = $this->kernel->isDebug();
        }

        if($kernelDebugState !== $debug) {
            $this->buildKernel($configuration, $debug);
        } elseif ($this->currentContainerConfiguration !== $configuration) {
            $this->buildKernel($configuration, $debug);
        }
    }

    /**
     * Actor Function to boot kernel without debug mode
     *
     * @part services
     */
    public function haveAKernelWithoutDebugMode(): void
    {
        $this->assertNotNull($this->currentContainerConfiguration, 'current container configuration must not be null');
        $this->buildKernel($this->currentContainerConfiguration, false);
    }
}

