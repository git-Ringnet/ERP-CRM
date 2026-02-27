<?php

namespace Tests\Unit\Services;

use App\Services\CacheService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class CacheServiceTest extends TestCase
{
    private CacheService $cacheService;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->cacheService = new CacheService(app('cache'));
    }

    public function test_remember_returns_cached_value_on_cache_hit(): void
    {
        $key = 'test_key';
        $ttl = 3600;
        $expectedValue = 'cached_value';
        
        // Pre-populate cache
        Cache::put($key, $expectedValue, $ttl);
        
        $callbackExecuted = false;
        $result = $this->cacheService->remember($key, $ttl, function() use (&$callbackExecuted) {
            $callbackExecuted = true;
            return 'computed_value';
        });
        
        $this->assertEquals($expectedValue, $result);
        $this->assertFalse($callbackExecuted, 'Callback should not be executed on cache hit');
    }

    public function test_remember_executes_callback_and_caches_on_cache_miss(): void
    {
        $key = 'test_key_miss';
        $ttl = 3600;
        $computedValue = 'computed_value';
        
        // Ensure cache is empty
        Cache::forget($key);
        
        $callbackExecuted = false;
        $result = $this->cacheService->remember($key, $ttl, function() use (&$callbackExecuted, $computedValue) {
            $callbackExecuted = true;
            return $computedValue;
        });
        
        $this->assertEquals($computedValue, $result);
        $this->assertTrue($callbackExecuted, 'Callback should be executed on cache miss');
        
        // Verify value is now cached
        $this->assertEquals($computedValue, Cache::get($key));
    }

    public function test_forget_removes_item_from_cache(): void
    {
        $key = 'test_key_forget';
        
        // Pre-populate cache
        Cache::put($key, 'some_value', 3600);
        $this->assertTrue(Cache::has($key));
        
        $result = $this->cacheService->forget($key);
        
        $this->assertTrue($result);
        $this->assertFalse(Cache::has($key));
    }

    public function test_forget_many_removes_multiple_items(): void
    {
        $keys = ['key1', 'key2', 'key3'];
        
        // Pre-populate cache
        foreach ($keys as $key) {
            Cache::put($key, "value_{$key}", 3600);
        }
        
        $this->cacheService->forgetMany($keys);
        
        // Verify all keys are removed
        foreach ($keys as $key) {
            $this->assertFalse(Cache::has($key));
        }
    }

    public function test_flush_clears_all_cache(): void
    {
        // Pre-populate cache with multiple items
        Cache::put('key1', 'value1', 3600);
        Cache::put('key2', 'value2', 3600);
        Cache::put('key3', 'value3', 3600);
        
        $result = $this->cacheService->flush();
        
        $this->assertTrue($result);
        $this->assertFalse(Cache::has('key1'));
        $this->assertFalse(Cache::has('key2'));
        $this->assertFalse(Cache::has('key3'));
    }
}
