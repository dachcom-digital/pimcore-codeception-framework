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

namespace Dachcom\Codeception\Support\Helper;

use Codeception\Lib\ModuleContainer;
use Codeception\Module;
use Dachcom\Codeception\Support\Util\ModuleHelper;

class PimcoreBundleCore extends Module
{
    public function __construct(ModuleContainer $moduleContainer, $config = null)
    {
        $this->config = array_merge($this->config, [
            'run_installer' => false
        ]);

        parent::__construct($moduleContainer, $config);
    }

    public function _beforeSuite($settings = [])
    {
        parent::_beforeSuite($settings);

        if ($this->config['run_installer'] === true) {
            $this->installBundle();
        }
    }

    protected function installBundle(): void
    {
        /** @var PimcoreCore $pimcoreCore */
        $pimcoreCore = $this->getModule(ModuleHelper::getModuleName('PIMCORE_CORE', PimcoreCore::class));

        $bundleName = getenv('TEST_BUNDLE_NAME');
        $installerClass = getenv('TEST_BUNDLE_INSTALLER_CLASS');

        if ($installerClass === false || $installerClass === 'false') {
            return;
        }

        $this->debug(sprintf('[%s] Running installer...', strtoupper($bundleName)));

        $pimcoreCore->runCommand('doctrine:migrations:sync-metadata-storage', ['-q']);

        $installer = $pimcoreCore->_getContainer()->get($installerClass);
        $installer->install();
    }
}
