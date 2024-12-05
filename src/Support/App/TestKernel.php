<?php

/*
 * This source file is available under two different licenses:
 *   - GNU General Public License version 3 (GPLv3)
 *   - DACHCOM Commercial License (DCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) DACHCOM.DIGITAL AG (https://www.dachcom-digital.com)
 * @license    GPLv3 and DCL
 */

use Pimcore\HttpKernel\BundleCollection\BundleCollection;
use Pimcore\Kernel;
use Symfony\Bundle\WebProfilerBundle\WebProfilerBundle;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\Config\Resource\ClassExistenceResource;
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
        'DependencyInjection/MonologChannelLoggerPass.php',
        'DependencyInjection/DisablePimcoreCsrfProtection.php'
    ];

    public function __construct(string $environment, bool $debug, ?string $runtimeConfigFile = null)
    {
        // fallback for acceptance testing (webdriver)
        if ($runtimeConfigFile === null) {
            $runtimeConfigFile = $_SERVER['APP_TEST_KERNEL_CONFIG'] ?? null;
        }

        $this->kernelName = is_string($runtimeConfigFile) ? str_replace('.yaml', '', $runtimeConfigFile) . ($debug ? '-debug' : '') : null;
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
                $collection->addBundle(new $testBundle['namespace'](), array_key_exists('priority', $testBundle) ? $testBundle['priority'] : -1000);
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
        $this->preloadClasses($container);

        $container->addCompilerPass(new \Dachcom\Codeception\Support\DependencyInjection\DisablePimcoreCsrfProtection());
        $container->addCompilerPass(new \Dachcom\Codeception\Support\DependencyInjection\MakeServicesPublicPass(), PassConfig::TYPE_BEFORE_OPTIMIZATION, -100000);
        $container->addCompilerPass(new \Dachcom\Codeception\Support\DependencyInjection\MonologChannelLoggerPass(), PassConfig::TYPE_BEFORE_OPTIMIZATION, 1);
    }

    protected function preloadClasses(ContainerBuilder $container): void
    {
        $fwDir = sprintf('%s/src', $_SERVER['PIMCORE_CODECEPTION_FRAMEWORK']);
        $bDir = sprintf('%s', $_SERVER['TEST_BUNDLE_TEST_DIR']);

        $bundlesFiles = [];
        $preloadFiles = $this->getTestBundleConfig('preload_files');

        if (is_array($preloadFiles)) {
            foreach ($preloadFiles as $preloadFile) {
                $bundlesFiles[] = $preloadFile['path'];
                $namespace = sprintf('DachcomBundle\Test\Support\%s', str_replace(['/', '.php'], ['\\', ''], $preloadFile['path']));
                $container->addResource(new ClassExistenceResource($namespace));
            }
        }

        foreach ([$bDir => $bundlesFiles, $fwDir => self::PRELOAD_FILES] as $dir => $files) {
            foreach ($files as $class) {
                $classPath = sprintf('%s/Support/%s', $dir, $class);
                include_once $classPath;
            }
        }
    }

    protected function getTestBundleConfig(string $section): mixed
    {
        $data = Yaml::parse(file_get_contents(sprintf('%s/_etc/config.yaml', $_SERVER['TEST_BUNDLE_TEST_DIR'])));

        return $data[$section] ?? null;
    }
}
