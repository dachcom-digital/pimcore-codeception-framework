<?php

namespace Dachcom\Codeception\Support\Util;

class ModuleHelper
{
    public static function getModuleName(string $name, string $fallback): string
    {
        $envName = sprintf('CODECEPTION_MODULE_%s', $name);

        return getenv($envName) !== false ? getenv($envName) : '\\' . $fallback;
    }
}
