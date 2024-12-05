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

use Codeception\Util\Autoload;
use Pimcore\Bootstrap;

if (!isset($_SERVER['REQUEST_URI'])) {
    $_SERVER['REQUEST_URI'] = '';
}

if (!isset($_SERVER['HTTP_USER_AGENT'])) {
    $_SERVER['HTTP_USER_AGENT'] = '';
}

if (file_exists(sprintf('%s/../autoload.php', __DIR__))) {
    include_once sprintf('%s/../autoload.php', __DIR__);
} elseif (file_exists(sprintf('%s/../../../autoload.php', __DIR__))) {
    include_once sprintf('%s/../../../autoload.php', __DIR__);
}

define('PIMCORE_TEST', true);

Bootstrap::setProjectRoot();

Autoload::addNamespace('Pimcore\Tests', PIMCORE_PROJECT_ROOT . '/vendor/pimcore/pimcore/tests');
Autoload::addNamespace('Dachcom\Codeception', __DIR__);
Autoload::addNamespace('Pimcore\Model\DataObject', sprintf('%s/_output/var/classes/DataObject', getenv('TEST_BUNDLE_TEST_DIR')));

// we need the real asset directory to also test asset protection via acceptance tests!
define('PIMCORE_ASSET_DIRECTORY', PIMCORE_PROJECT_ROOT . '/web/var/assets');

Bootstrap::bootstrap();

if (!defined('TESTS_PATH')) {
    define('TESTS_PATH', getenv('TEST_BUNDLE_TEST_DIR'));
}
