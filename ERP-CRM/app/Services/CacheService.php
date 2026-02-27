<?php

namespace App\Services;

use Closure;
use Exception;
use Illuminate\Cache\CacheManager;
use Illuminate\Support\Facades\Log;

class CacheService implements CacheServiceInterface
{
    /**
     * Create a new CacheService instance.
     *
     * @param CacheManager $cache
     */
    public function __construct(
        private CacheManager $cache
    ) {}

    /**
     * Get an item from the cache, or execute the given Closure and store the result.
     * 
     * Implements graceful degradation: if Redis is unavailable, executes callback directly.
     *
     * @param string $key Cache key
     * @param int $ttl Time to live in seconds
     * @param Closure $callback Callback to execute if cache miss
     * @return mixed
     */
    public function remember(string $key, int $ttl, Closure $callback): mixed
    {
        try {
            return $this->cache->remember($key, $ttl, $callback);
        } catch (Exception $e) {
            Log::warning('Cache unavailable, computing directly', [
                'key' => $key,
                'error' => $e->getMessage(),
            ]);
            return $callback();
        }
    }

    /**
     * Remove an item from the cache.
     * 
     * Implements graceful degradation: if Redis is unavailable, logs warning and returns false.
     *
     * @param string $key Cache key
     * @return bool True if the item was removed, false otherwise
     */
    public function forget(string $key): bool
    {
        try {
            return $this->cache->forget($key);
        } catch (Exception $e) {
            Log::warning('Cache unavailable, cannot forget key', [
                'key' => $key,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Remove multiple items from the cache.
     * 
     * Implements graceful degradation: if Redis is unavailable, logs warning and continues.
     *
     * @param array $keys Array of cache keys
     * @return void
     */
    public function forgetMany(array $keys): void
    {
        try {
            foreach ($keys as $key) {
                $this->cache->forget($key);
            }
        } catch (Exception $e) {
            Log::warning('Cache unavailable, cannot forget keys', [
                'keys' => $keys,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Remove all items from the cache.
     * 
     * Implements graceful degradation: if Redis is unavailable, logs warning and returns false.
     *
     * @return bool True if the cache was flushed, false otherwise
     */
    public function flush(): bool
    {
        try {
            return $this->cache->flush();
        } catch (Exception $e) {
            Log::warning('Cache unavailable, cannot flush', [
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }
}
