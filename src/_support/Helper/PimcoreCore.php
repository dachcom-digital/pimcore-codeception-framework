<?php

namespace Dachcom\Codeception\Helper;

use Codeception\Lib\Connector\Symfony as SymfonyConnector;
use Codeception\Lib\ModuleContainer;
use Codeception\Module\Symfony;
use Codeception\TestInterface;
use Dachcom\Codeception\Util\DbHelper;
use Dachcom\Codeception\Util\KernelHelper;
use Doctrine\DBAL\Connection;
use Pimcore\Cache;
use Pimcore\Event\TestEvents;
use Pimcore\Model\DataObject\ClassDefinition\ClassDefinitionManager;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\GenericEvent;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\HttpKernel\KernelInterface;

class PimcoreCore extends Symfony
{
    protected bool $dbInitialized = false;
    protected ?string $currentContainerConfiguration = null;

    public const DEFAULT_CONFIG_FILE = 'config_default.yml';

    public function __construct(ModuleContainer $moduleContainer, $config = null)
    {
        $this->config = array_merge($this->config, [
            'skip_db_tests'         => getenv('PIMCORE_TEST_SKIP_DB'),
            'initialize_db'         => true,
            'connect_db'            => false,
            'configuration_file'    => null,
            'debug'                 => true,
            'purge_class_directory' => true,
            'setup_objects'         => false,
        ]);

        parent::__construct($moduleContainer, $config);
    }

    public function getKernel(): ?Kernel
    {
        return $this->kernel;
    }

    public function _initialize(): void
    {
        $this->setupTestEnvironment();
        $this->setupPimcoreDirectories();
    }

    public function _beforeSuite($settings = []): void
    {
        parent::_beforeSuite($settings);

        $this->buildKernel($this->getDefaultSuiteContainerConfiguration(), $this->config['debug'], '_beforeSuite');
        $this->checkDatabaseState();
        $this->purgeClassDirectory();
        $this->setupPimcoreObjects();
    }

    public function _before(TestInterface $test): void
    {
        parent::_before($test);

        // always reset admin mode before starting a test
        \Pimcore::unsetAdminMode();

        if ($this->containerDefaultsChanged() === false) {
            return;
        }

        $this->buildKernel($this->getDefaultSuiteContainerConfiguration(), $this->config['debug'], '_before');
    }

    protected function checkDatabaseState(): void
    {
        if ($this->dbInitialized === true) {
            return;
        }

        $this->setupDbConnection();
        $this->dbInitialized = true;
    }

    protected function getDefaultSuiteContainerConfiguration(): string
    {
        $configuration = self::DEFAULT_CONFIG_FILE;

        if ($this->config['configuration_file'] !== null && $this->config['configuration_file'] !== self::DEFAULT_CONFIG_FILE) {
            $configuration = $this->config['configuration_file'];
        }

        return $configuration;
    }

    protected function buildKernel(string $configuration, bool $debug, string $dispatcher): void
    {
        // nothing to do. kernel hasn't changed
        if (($this->currentContainerConfiguration === $configuration) && $this->kernel->isDebug() === $debug) {
            return;
        }

        if ($this->currentContainerConfiguration === null) {
            codecept_debug(sprintf('Building Kernel with configuration "%s"', $configuration));
        } else {
            codecept_debug(sprintf('Container defaults or debug mode changed by "%s" to "%s". Rebuilding Kernel...', $dispatcher, $configuration));
        }

        // set new configuration
        $this->currentContainerConfiguration = $configuration;

        // this is required to also push acceptance tests into right kernel context
        // acceptance tests are using webdriver module which will load pimcore's default index.php => no custom kernel build available!
        KernelHelper::setLocalEnvVarsForRemoteKernel(['APP_DEBUG' => $debug ? '1' : '0', 'APP_TEST_KERNEL_CONFIG' => $configuration]);

        if ($this->kernel) {

            // invalidate pimcore session
            \Pimcore\Tool\Session::invalidate();

            $this->kernel->shutdown();
            \Pimcore::shutdown();
        }

        $this->kernel = KernelHelper::buildTestKernel($debug, $configuration);

        // config/debug may have changed during test (via actor), so we need to reset clients kernel too!
        if ($this->client instanceof SymfonyConnector) {
            $this->client = new SymfonyConnector($this->kernel, $this->persistentServices, $this->config['rebootable_client']);
            //$this->rebootClientKernel();
        }

        if (array_key_exists('cache_router', $this->config) && $this->config['cache_router'] === true) {
            $this->persistService('router', true);
        }

        // dispatch kernel booted event - will be used from services which need to reset state between tests
        $this->kernel->getContainer()->get('event_dispatcher')?->dispatch(new GenericEvent(), TestEvents::KERNEL_BOOTED);

        $this->setPimcoreCacheAvailability('disabled');
    }

    protected function setupDbConnection(): void
    {
        if (!$this->config['connect_db']) {
            return;
        }

        if ($this->config['skip_db_tests']) {
            $this->debug('[DB] Not connecting to DB as skip_db_tests is set');

            return;
        }

        /** @var LoggerInterface $logger */
        $logger = $this->_getContainer()->get('monolog.logger.pimcore');
        /** @var EventDispatcherInterface $eventDispatcher */
        $eventDispatcher = $this->_getContainer()->get('event_dispatcher');

        DbHelper::setupDb(
            $this->getDbConnection(),
            $this->config['initialize_db'],
            $logger,
            $eventDispatcher
        );
    }

    protected function setupTestEnvironment(): void
    {
        $maxNestingLevel = 200; // Symfony may have very long nesting level
        $xdebugMaxLevelKey = 'xdebug.max_nesting_level';
        if (ini_get($xdebugMaxLevelKey) < $maxNestingLevel) {
            ini_set($xdebugMaxLevelKey, $maxNestingLevel);
        }
    }

    protected function setPimcoreCacheAvailability(string $state = 'disabled'): void
    {
        if ($state === 'disabled') {
            Cache::disable();
        } else {
            Cache::enable();
        }
    }

    protected function setupPimcoreDirectories(): void
    {
        $directories = [
            PIMCORE_CLASS_DIRECTORY,
            PIMCORE_CLASS_DEFINITION_DIRECTORY,
        ];

        $filesystem = new Filesystem();
        foreach ($directories as $directory) {
            if (!$filesystem->exists($directory)) {
                $filesystem->mkdir($directory, 0755);
            }
        }
    }

    protected function setupPimcoreObjects(): void
    {
        if (!$this->config['initialize_db']) {
            return;
        }

        if (!$this->config['setup_objects']) {
            return;
        }

        $this->debug('[DB] Initializing objects');
        $this->kernel->getContainer()->get(ClassDefinitionManager::class)->createOrUpdateClassDefinitions();
    }

    protected function purgeClassDirectory(): void
    {
        if (!$this->config['purge_class_directory']) {
            return;
        }

        $directories = [
            PIMCORE_CLASS_DIRECTORY,
            PIMCORE_CLASS_DEFINITION_DIRECTORY,
        ];

        $filesystem = new Filesystem();
        foreach ($directories as $directory) {
            if (file_exists($directory)) {
                $this->debug('[INIT] Purging class directory ' . $directory);

                $filesystem->remove($directory);
                $filesystem->mkdir($directory, 0755);
            }
        }
    }

    protected function containerDefaultsChanged(): bool
    {
        $kernelDebugState = true;
        $defaultDebugState = $this->config['debug'];

        if ($this->kernel instanceof KernelInterface) {
            $kernelDebugState = $this->kernel->isDebug();
        }

        if ($this->currentContainerConfiguration !== $this->getDefaultSuiteContainerConfiguration()) {
            return true;
        }

        if ($kernelDebugState !== $defaultDebugState) {
            return true;
        }

        return false;
    }

    protected function getDbConnection(): Connection
    {
        /** @var Connection $connection */
        $connection = $this->_getContainer()->get('database_connection');

        return $connection;
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

                if (get_class($e) === $exception || get_parent_class($e) === $exception) {

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

        if ($kernelDebugState !== $debug) {
            $this->buildKernel($configuration, $debug, '_actor[haveABootedSymfonyConfiguration]');
        } elseif ($this->currentContainerConfiguration !== $configuration) {
            $this->buildKernel($configuration, $debug, '_actor[haveABootedSymfonyConfiguration]');
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
        $this->buildKernel($this->currentContainerConfiguration, false, '_actor[haveAKernelWithoutDebugMode]');
    }
}

