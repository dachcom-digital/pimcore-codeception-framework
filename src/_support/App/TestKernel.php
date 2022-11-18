<?php

use Pimcore\Kernel;
use Pimcore\HttpKernel\BundleCollection\BundleCollection;
use Symfony\Bundle\WebProfilerBundle\WebProfilerBundle;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\Yaml\Yaml;

class TestKernel extends Kernel
{
    protected ?string $kernelName;
    protected ?string $runtimeConfigFile;

    public const PRELOAD_FILES = [
        'DependencyInjection/MakeServicesPublicPass.php',
        'DependencyInjection/MonologChannelLoggerPass.php'
    ];

    public function __construct(string $environment, bool $debug, ?string $runtimeConfigFile = null)
    {
        // fallback for acceptance testing (webdriver)
        if ($runtimeConfigFile === null) {
            $runtimeConfigFile = $_SERVER['APP_TEST_KERNEL_CONFIG'] ?? null;
        }

        $this->kernelName = is_string($runtimeConfigFile) ? str_replace('.yml', '', $runtimeConfigFile) : null;
        $this->runtimeConfigFile = $runtimeConfigFile;

        parent::__construct($environment, $debug);
    }

    public function getCacheDir(): string
    {
        if ($this->kernelName === null) {
            return parent::getCacheDir();
        }

        return sprintf(
            '%s/var/cache/%s/%s',
            $this->getProjectDir(),
            $this->kernelName,
            $this->environment
        );
    }

    public function registerBundlesToCollection(BundleCollection $collection): void
    {
        $collection->addBundle(new WebProfilerBundle());

        $testBundles = $this->getTestBundleConfig('bundles');

        if (is_array($testBundles)) {
            foreach ($testBundles as $testBundle) {
                $collection->addBundle(new $testBundle['namespace'], -1000);
            }
        }
    }

    public function registerContainerConfiguration(LoaderInterface $loader): void
    {
        parent::registerContainerConfiguration($loader);

        if ($this->runtimeConfigFile === null) {
            return;
        }

        $loader->load(function (ContainerBuilder $container) {

            $runtimeConfigDir = sprintf('%s/_etc', $_SERVER['TEST_BUNDLE_TEST_DIR']);
            $runtimeConfigDir = sprintf('%s/config/bundle/', $runtimeConfigDir);

            $loader = new YamlFileLoader($container, new FileLocator([$runtimeConfigDir]));
            $loader->load($this->runtimeConfigFile);

        });
    }

    protected function build(ContainerBuilder $container): void
    {
        $this->preloadClasses();

        $container->addCompilerPass(new \Dachcom\Codeception\DependencyInjection\MakeServicesPublicPass(), PassConfig::TYPE_BEFORE_OPTIMIZATION, -100000);
        $container->addCompilerPass(new \Dachcom\Codeception\DependencyInjection\MonologChannelLoggerPass(), PassConfig::TYPE_BEFORE_OPTIMIZATION, 1);
        $container->addCompilerPass(new \Dachcom\Codeception\DependencyInjection\ServiceReplacePass(), PassConfig::TYPE_BEFORE_REMOVING, 250);
    }

    protected function preloadClasses(): void
    {
        $fwDir = sprintf('%s/src', $_SERVER['PIMCORE_CODECEPTION_FRAMEWORK']);
        $bDir = sprintf('%s', $_SERVER['TEST_BUNDLE_TEST_DIR']);

        $bundlesFiles = [];
        $preloadFiles = $this->getTestBundleConfig('preload_files');

        if (is_array($preloadFiles)) {
            foreach ($preloadFiles as $preloadFile) {
                $bundlesFiles[] = $preloadFile['path'];
            }
        }

        foreach ([$bDir => $bundlesFiles, $fwDir => self::PRELOAD_FILES] as $dir => $files) {
            foreach ($files as $class) {
                $classPath = sprintf('%s/_support/%s', $dir, $class);
                include_once $classPath;
            }
        }
    }

    protected function getTestBundleConfig(string $section): mixed
    {
        $data = Yaml::parse(file_get_contents(sprintf('%s/_etc/config.yml', $_SERVER['TEST_BUNDLE_TEST_DIR'])));

        return $data[$section] ?? null;
    }
}
