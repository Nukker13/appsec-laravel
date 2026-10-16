<?php

namespace Tests\Support;

use Illuminate\Support\Facades\Redis;

/**
 * Points cache and locks at a real Redis server, on database 15 (the platform uses 0 and 1), emptied around each test.
 */
trait UsesRealRedis
{
    /** @return array<string, string> environment that makes another PHP process use the same Redis setup */
    protected function redisEnvironment(): array
    {
        return [
            'CACHE_STORE' => 'redis',
            'QUEUE_CONNECTION' => 'sync',
            'REDIS_CACHE_DB' => '15',
            'REDIS_CACHE_LOCK_CONNECTION' => 'cache',
        ];
    }

    protected function useRealRedis(): void
    {
        config()->set('cache.default', 'redis');
        config()->set('database.redis.cache.database', '15');
        config()->set('cache.stores.redis.lock_connection', 'cache');

        $this->app->make('redis')->purge('cache');
        $this->app->make('cache')->forgetDriver('redis');
        $this->app->forgetInstance('cache.store');

        Redis::connection('cache')->flushdb();
    }

    protected function releaseRealRedis(): void
    {
        Redis::connection('cache')->flushdb();
    }
}
