<?php

use Codeception\Util\Autoload;
use Pimcore\Bootstrap;

if (file_exists(sprintf('%s/../autoload.php', __DIR__))) {
    include_once sprintf('%s/../autoload.php', __DIR__);
} elseif (file_exists(sprintf('%s/../../../autoload.php', __DIR__))) {
    include_once sprintf('%s/../../../autoload.php', __DIR__);
}

define('PIMCORE_KERNEL_CLASS', '\Dachcom\Codeception\App\TestAppKernel');
define('PIMCORE_TEST', true);

Bootstrap::setProjectRoot();
Bootstrap::bootstrap();

Autoload::addNamespace('Pimcore\Tests', PIMCORE_PROJECT_ROOT . '/vendor/pimcore/pimcore/tests/_support');
Autoload::addNamespace('Dachcom\Codeception', __DIR__ . '/_support');
Autoload::addNamespace('Pimcore\Model\DataObject', __DIR__ . '/_output/var/classes/DataObject');

if (!defined('TESTS_PATH')) {
    define('TESTS_PATH', __DIR__);
}

if (!isset($_SERVER['REQUEST_URI'])) {
    $_SERVER['REQUEST_URI'] = '';
}

if (!isset($_SERVER['HTTP_USER_AGENT'])) {
    $_SERVER['HTTP_USER_AGENT'] = '';
}
