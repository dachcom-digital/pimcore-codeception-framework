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

class ModuleHelper
{
    public static function getModuleName(string $name, string $fallback): string
    {
        $envName = sprintf('CODECEPTION_MODULE_%s', $name);

        return getenv($envName) !== false ? getenv($envName) : '\\' . $fallback;
    }
}
