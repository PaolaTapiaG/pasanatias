<?php

namespace App\Support;

use Closure;
use Illuminate\Support\Facades\Cache;

final class OperationalCache
{
    private const VERSION_KEY = 'tecnico:operational-cache-version';
    private const PREFIX = 'tecnico:operational';

    public static function remember(string $name, Closure $callback, mixed $ttl = null): mixed
    {
        return Cache::remember(
            self::key($name),
            $ttl ?? now()->addDays(7),
            $callback
        );
    }

    public static function key(string $name): string
    {
        return self::PREFIX . ':v' . self::version() . ':' . $name;
    }

    public static function version(): int
    {
        Cache::add(self::VERSION_KEY, 1, now()->addYears(2));

        return (int) Cache::get(self::VERSION_KEY, 1);
    }

    public static function bump(): void
    {
        Cache::add(self::VERSION_KEY, 1, now()->addYears(2));
        Cache::increment(self::VERSION_KEY);
    }
}
