<?php

namespace Stillat\Dagger\Support;

use Illuminate\Support\Str;

class Utils
{
    public static function normalizePath(string $path): string
    {
        return str_replace('\\', '/', $path);
    }

    public static function arePathsEqual(string $pathA, string $pathB): bool
    {
        return self::normalizePath($pathA) == self::normalizePath($pathB);
    }

    public static function makeRandomString(): string
    {
        return '_'.Str::random(24);
    }

    public static function getNewlineStyle(string $input): string
    {
        $lineEnding = "\n";

        if (Str::contains($input, "\r\n")) {
            $lineEnding = "\r\n";
        }

        return $lineEnding;
    }

    public static function normalizeComponentName(string $name): string
    {
        return str_replace(':', '.', $name);
    }
}
