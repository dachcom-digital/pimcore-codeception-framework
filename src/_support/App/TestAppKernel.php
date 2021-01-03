<?php

namespace Dachcom\Codeception\App;

use Pimcore\Kernel;
use Dachcom\Codeception\DependencyInjection\ServiceChangePass;
use Dachcom\Codeception\DependencyInjection\MakeServicesPublicPass;
use Dachcom\Codeception\DependencyInjection\MonologChannelLoggerPass;
use Pimcore\HttpKernel\BundleCollection\BundleCollection;
use Symfony\Bundle\WebProfilerBundle\WebProfilerBundle;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

class TestAppKernel extends Kernel
{
    /**
     * {@inheritdoc}
     */
    public function registerBundlesToCollection(BundleCollection $collection)
    {
        $collection->addBundle(new WebProfilerBundle());

        $bundleNamespace = getenv('TEST_BUNDLE_NAMESPACE');
        $collection->addBundle(new $bundleNamespace());

        if (class_exists('\\AppBundle\\AppBundle')) {
            $collection->addBundle(new \AppBundle\AppBundle());
        }
    }

    /**
     * {@inheritdoc}
     */
    public function registerContainerConfiguration(LoaderInterface $loader)
    {
        parent::registerContainerConfiguration($loader);

        $loader->load(function (ContainerBuilder $container) {
            $runtimeConfigDir = codecept_data_dir() . 'config' . DIRECTORY_SEPARATOR;
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
        $container->addCompilerPass(new ServiceChangePass(), PassConfig::TYPE_BEFORE_OPTIMIZATION, -100000);
        $container->addCompilerPass(new MakeServicesPublicPass(), PassConfig::TYPE_BEFORE_OPTIMIZATION, -100000);
        $container->addCompilerPass(new MonologChannelLoggerPass(), PassConfig::TYPE_BEFORE_OPTIMIZATION, 1);
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
