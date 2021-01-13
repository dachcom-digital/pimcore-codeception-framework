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
    const PRELOAD_FILES = [
        'App/Pimcore/TestConfig.php',
        'App/Session/MockFileSessionStorage.php',
        'DependencyInjection/MakeServicesPublicPass.php',
        'DependencyInjection/MonologChannelLoggerPass.php',
        'DependencyInjection/ServiceChangePass.php',
    ];

    /**
     * {@inheritdoc}
     */
    public function registerBundlesToCollection(BundleCollection $collection)
    {
        $collection->addBundle(new WebProfilerBundle());

        if (class_exists('\AppBundle\AppBundle')) {
            $collection->addBundle(new \AppBundle\AppBundle());
        }

        $testBundles = $this->getTestBundleConfig('bundles');

        if (is_array($testBundles)) {
            foreach ($testBundles as $testBundle) {
                $collection->addBundle(new $testBundle['namespace'], -1000);
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function registerContainerConfiguration(LoaderInterface $loader)
    {
        parent::registerContainerConfiguration($loader);

        $loader->load(function (ContainerBuilder $container) {

            $dataDir = sprintf('%s/_data', $_SERVER['TEST_BUNDLE_TEST_DIR']);
            $runtimeConfigDir = sprintf('%s/config/', $dataDir);

            $loader = new YamlFileLoader($container, new FileLocator([$runtimeConfigDir]));
            $loader->load('config.yml');

        });
    }

    /**
     * @param ContainerBuilder $container
     *
     * @throws \Exception
     */
    protected function build(ContainerBuilder $container)
    {
        $this->preloadClasses();

        $container->addCompilerPass(new \Dachcom\Codeception\DependencyInjection\ServiceChangePass(), PassConfig::TYPE_BEFORE_OPTIMIZATION, -100000);
        $container->addCompilerPass(new \Dachcom\Codeception\DependencyInjection\MakeServicesPublicPass(), PassConfig::TYPE_BEFORE_OPTIMIZATION, -100000);
        $container->addCompilerPass(new \Dachcom\Codeception\DependencyInjection\MonologChannelLoggerPass(), PassConfig::TYPE_BEFORE_OPTIMIZATION, 1);
    }

    protected function preloadClasses()
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

    /**
     * @param string $section
     *
     * @return mixed|null
     */
    protected function getTestBundleConfig(string $section)
    {
        $data = Yaml::parse(file_get_contents(sprintf('%s/_etc/config.yml', $_SERVER['TEST_BUNDLE_TEST_DIR'])));

        return isset($data[$section]) ? $data[$section] : null;
    }

    /**
     * {@inheritdoc}
     */
    public function boot()
    {
        parent::boot();
        \Pimcore::setKernel($this);
    }
}
