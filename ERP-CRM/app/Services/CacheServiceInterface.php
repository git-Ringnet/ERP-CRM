<?php

namespace App\Services;

use Closure;

interface CacheServiceInterface
{
    /**
     * Get an item from the cache, or execute the given Closure and store the result.
     *
     * @param string $key Cache key
     * @param int $ttl Time to live in seconds
     * @param Closure $callback Callback to execute if cache miss
     * @return mixed
     */
    public function remember(string $key, int $ttl, Closure $callback): mixed;

    /**
     * Remove an item from the cache.
     *
     * @param string $key Cache key
     * @return bool True if the item was removed, false otherwise
     */
    public function forget(string $key): bool;

    /**
     * Remove multiple items from the cache.
     *
     * @param array $keys Array of cache keys
     * @return void
     */
    public function forgetMany(array $keys): void;

    /**
     * Remove all items from the cache.
     *
     * @return bool True if the cache was flushed, false otherwise
     */
    public function flush(): bool;
}
