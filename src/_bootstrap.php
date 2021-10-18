<?php

use Codeception\Util\Autoload;
use Pimcore\Bootstrap;

if (file_exists(sprintf('%s/../autoload.php', __DIR__))) {
    include_once sprintf('%s/../autoload.php', __DIR__);
} elseif (file_exists(sprintf('%s/../../../autoload.php', __DIR__))) {
    include_once sprintf('%s/../../../autoload.php', __DIR__);
}

$_ENV['PIMCORE_WRITE_TARGET_IMAGE_THUMBNAILS'] = 'settings-store';
$_ENV['PIMCORE_WRITE_TARGET_CUSTOM_REPORTS'] = 'settings-store';
$_ENV['PIMCORE_WRITE_TARGET_VIDEO_THUMBNAILS'] = 'settings-store';
$_ENV['PIMCORE_WRITE_TARGET_DOCUMENT_TYPES'] = 'settings-store';
$_ENV['PIMCORE_WRITE_TARGET_WEB_TO_PRINT'] = 'settings-store';
$_ENV['PIMCORE_WRITE_TARGET_PREDEFINED_PROPERTIES'] = 'settings-store';
$_ENV['PIMCORE_WRITE_TARGET_PREDEFINED_ASSET_METADATA'] = 'settings-store';
$_ENV['PIMCORE_WRITE_TARGET_STATICROUTES'] = 'settings-store';

define('PIMCORE_TEST', true);

Bootstrap::setProjectRoot();

Autoload::addNamespace('Pimcore\Tests', PIMCORE_PROJECT_ROOT . '/vendor/pimcore/pimcore/tests/_support');
Autoload::addNamespace('Dachcom\Codeception', __DIR__ . '/_support');
Autoload::addNamespace('Pimcore\Model\DataObject', sprintf('%s/_output/var/classes/DataObject', getenv('TEST_BUNDLE_TEST_DIR')));

# we need the real asset directory to also test asset protection via acceptance tests!
define('PIMCORE_ASSET_DIRECTORY', PIMCORE_PROJECT_ROOT . '/web/var/assets');

Bootstrap::bootstrap();

if (!defined('TESTS_PATH')) {
    define('TESTS_PATH', getenv('TEST_BUNDLE_TEST_DIR'));
}

if (!isset($_SERVER['REQUEST_URI'])) {
    $_SERVER['REQUEST_URI'] = '';
}

if (!isset($_SERVER['HTTP_USER_AGENT'])) {
    $_SERVER['HTTP_USER_AGENT'] = '';
}
