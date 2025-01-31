<?php

namespace Stillat\Dagger\Cache;

use Illuminate\Support\Str;

class CacheAttributeParser
{
    protected static function parseDurationIntoParts(string $duration): int|array
    {
        $duration = strtolower(trim($duration));

        $pattern = '/(\d+)(y|mo|w|d|h|m|s)/';

        preg_match_all($pattern, $duration, $matches, PREG_SET_ORDER);

        $years = 0;
        $months = 0;
        $weeks = 0;
        $days = 0;
        $hours = 0;
        $minutes = 0;
        $seconds = 0;

        foreach ($matches as $match) {
            $value = (int) $match[1];
            $unit = $match[2];

            switch ($unit) {
                case 'y':
                    $years += $value;
                    break;
                case 'mo':
                    $months += $value;
                    break;
                case 'w':
                    $weeks += $value;
                    break;
                case 'd':
                    $days += $value;
                    break;
                case 'h':
                    $hours += $value;
                    break;
                case 'm':
                    $minutes += $value;
                    break;
                case 's':
                    $seconds += $value;
                    break;
            }
        }

        return [
            $years,
            $months,
            $weeks,
            $days,
            $hours,
            $minutes,
            $seconds,
        ];
    }

    protected static function getDuration(array $parts): string|array
    {
        $duration = trim($parts[1] ?? 'forever');

        if ($duration === 'forever') {
            return $duration;
        }

        if (Str::contains($duration, ':')) {
            $durationParts = explode(':', $duration);
            $duration = array_shift($durationParts);

            return [
                $duration,
                array_values($durationParts),
            ];
        }

        if (is_numeric($duration)) {
            return $duration;
        }

        return [static::parseDurationIntoParts($duration), []];
    }

    protected static function getStore(array $parts)
    {
        return $parts[2] ?? cache()->getDefaultDriver();
    }

    public static function parseCacheString(string $cache): CacheProperties
    {
        if (! Str::startsWith($cache, 'cache.') && $cache !== 'cache') {
            $cache = 'cache.'.$cache;
        }

        $parts = explode('.', $cache);

        $extraArgs = [];
        $store = static::getStore($parts);
        $duration = static::getDuration($parts);

        if (is_array($duration)) {
            $extraArgs = $duration[1];
            $duration = $duration[0];
        }

        return new CacheProperties(
            $duration,
            $store,
            $extraArgs
        );
    }
}
