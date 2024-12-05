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

class VersionHelper
{
    public static function pimcoreVersionIsEqualThan(string $version): int|bool
    {
        return version_compare(self::getPimcoreVersion(), $version, '=');
    }

    public static function pimcoreVersionIsGreaterThan(string $version): int|bool
    {
        return version_compare(self::getPimcoreVersion(), $version, '>');
    }

    public static function pimcoreVersionIsGreaterOrEqualThan(string $version): int|bool
    {
        return version_compare(self::getPimcoreVersion(), $version, '>=');
    }

    public static function pimcoreVersionIsLowerThan(string $version): int|bool
    {
        return version_compare(self::getPimcoreVersion(), $version, '<');
    }

    public static function pimcoreVersionIsLowerOrEqualThan(string $version): int|bool
    {
        return version_compare(self::getPimcoreVersion(), $version, '<=');
    }

    private static function getPimcoreVersion(): string
    {
        return preg_replace('/[^0-9.]/', '', \Pimcore\Version::getVersion());
    }
}
