<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

/**
 * Centralized cache management for CASI360.
 *
 * Provides consistent key generation, TTL configuration,
 * and module-based invalidation that works with any cache driver.
 */
class CacheService
{
    /**
     * Default TTLs in seconds per cache group.
     */
    private const TTL = [
        'permission'  => 300,   // 5 min — role-permission lookups
        'settings'    => 600,   // 10 min — system settings
        'reference'   => 120,   // 2 min — departments, designations, vendors, categories
        'list'        => 60,    // 1 min — paginated list endpoints
        'stats'       => 120,   // 2 min — dashboard stats
        'report'      => 180,   // 3 min — report previews (not downloads)
    ];

    /**
     * Get configured TTL for a cache group.
     */
    public static function ttl(string $group): int
    {
        return self::TTL[$group] ?? 60;
    }

    /**
     * Build a response cache key from the request context.
     */
    public static function responseKey(string $url, array $query, ?string $userId): string
    {
        ksort($query);
        $queryHash = md5(serialize($query));
        $pathHash = md5($url);

        return "resp:{$pathHash}:{$queryHash}:" . ($userId ?? 'anon');
    }

    /**
     * Build a permission cache key.
     */
    public static function permissionKey(string $role, string $permission): string
    {
        return "perm:{$role}:{$permission}";
    }

    /**
     * Build a module-scoped cache key.
     */
    public static function moduleKey(string $module, string $identifier): string
    {
        return "mod:{$module}:{$identifier}";
    }

    /**
     * Register a cache key under a module for later invalidation.
     * Uses a registry pattern that works with all cache drivers (no tags needed).
     */
    public static function register(string $module, string $key): void
    {
        $registryKey = "registry:{$module}";
        $keys = Cache::get($registryKey, []);
        $keys[$key] = true;

        // Keep registry alive for 24 hours (max cache lifetime)
        Cache::put($registryKey, $keys, 86400);
    }

    /**
     * Invalidate all cached entries for a module.
     */
    public static function invalidateModule(string $module): void
    {
        $registryKey = "registry:{$module}";
        $keys = Cache::get($registryKey, []);

        foreach (array_keys($keys) as $key) {
            Cache::forget($key);
        }

        Cache::forget($registryKey);
    }

    /**
     * Invalidate all response cache for a module.
     */
    public static function invalidateResponses(string $module): void
    {
        // Response cache keys are registered under "response:{module}"
        self::invalidateModule("response:{$module}");
    }

    /**
     * Invalidate permission cache for all roles.
     */
    public static function invalidatePermissions(): void
    {
        self::invalidateModule('permissions');
    }

    /**
     * Flush all application cache.
     */
    public static function flush(): void
    {
        Cache::flush();
    }

    /**
     * Remember a value with automatic module registration for invalidation.
     */
    public static function remember(string $module, string $key, int $ttl, callable $callback): mixed
    {
        $fullKey = self::moduleKey($module, $key);
        self::register($module, $fullKey);

        return Cache::remember($fullKey, $ttl, $callback);
    }
}
