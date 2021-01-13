<?php

namespace Dachcom\Codeception\Helper;

use Codeception\Exception\ModuleException;
use Codeception\Lib\ModuleContainer;
use Codeception\Module;

class PimcoreBundleCore extends Module
{
    /**
     * @inheritDoc
     */
    public function __construct(ModuleContainer $moduleContainer, $config = null)
    {
        $this->config = array_merge($this->config, [
            'run_installer' => false
        ]);

        parent::__construct($moduleContainer, $config);
    }

    /**
     * @param array $settings
     *
     * @throws ModuleException
     */
    public function _beforeSuite($settings = [])
    {
        parent::_beforeSuite($settings);

        if ($this->config['run_installer'] === true) {
            $this->installBundle($settings);
        }
    }

    /**
     * @param $settings
     *
     * @throws ModuleException
     */
    protected function installBundle($settings)
    {
        /** @var PimcoreCore $pimcoreModule */
        $pimcoreModule = $this->getModule('\\' . PimcoreCore::class);

        $bundleName = getenv('TEST_BUNDLE_NAME');
        $installerClass = getenv('TEST_BUNDLE_INSTALLER_CLASS');

        if ($installerClass === false || $installerClass === 'false') {
            return;
        }

        $this->debug(sprintf('[%s] Running installer...', strtoupper($bundleName)));

        // install bundle
        $installer = $pimcoreModule->getContainer()->get($installerClass);
        $installer->install();
    }
}
