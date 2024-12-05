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

namespace Dachcom\Codeception\Support\Util;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

class FileGeneratorHelper
{
    public static function generateDummyFile(string $fileName, int $fileSizeInMb = 1): void
    {
        $dataDir = self::getStoragePath();

        if (file_exists($dataDir . $fileName)) {
            return;
        }

        $bytes = $fileSizeInMb * 1000000;
        $fp = fopen($dataDir . $fileName, 'w');
        fseek($fp, $bytes - 1, SEEK_CUR);
        fwrite($fp, 'a');
        fclose($fp);
    }

    public static function preparePaths(): void
    {
        $fs = new Filesystem();
        $dataDir = codecept_data_dir();

        if (!$fs->exists($dataDir . 'generated')) {
            $fs->mkdir($dataDir . 'generated');
        }

        if (!$fs->exists($dataDir . 'downloads')) {
            $fs->mkdir($dataDir . 'downloads');
        }
    }

    public static function getStoragePath(): string
    {
        return codecept_data_dir() . 'generated' . DIRECTORY_SEPARATOR;
    }

    public static function getDownloadPath(): string
    {
        return codecept_data_dir() . 'downloads' . DIRECTORY_SEPARATOR;
    }

    public static function getWebdriverDownloadPath(): string
    {
        return getenv('WEBDRIVER_DOWNLOAD_PATH') !== false
            ? getenv('WEBDRIVER_DOWNLOAD_PATH')
            : codecept_data_dir() . 'downloads' . DIRECTORY_SEPARATOR;
    }

    public static function cleanUp(): void
    {
        $finder = new Finder();
        $fs = new Filesystem();

        $dataDir = self::getStoragePath();
        if ($fs->exists($dataDir)) {
            $fs->remove($finder->ignoreDotFiles(true)->in($dataDir));
        }

        $downloadDir = self::getDownloadPath();
        if ($fs->exists($downloadDir)) {
            $fs->remove($finder->ignoreDotFiles(true)->in($downloadDir));
        }
    }
}
