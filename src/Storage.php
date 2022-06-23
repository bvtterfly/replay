<?php

namespace Bvtterfly\Replay;

use Bvtterfly\Replay\Exceptions\InvalidConfiguration;
use Illuminate\Cache\TaggableStore;
use Illuminate\Cache\TaggedCache;
use Illuminate\Contracts\Cache\Lock;
use Illuminate\Contracts\Cache\LockProvider;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Support\Facades\Cache;

class Storage
{
    public static function get(string $key): ?ReplayResponse
    {
        return self::tagged()->get(self::getCacheKey($key));
    }

    public static function put(string $key, ReplayResponse $response): void
    {
        self::tagged()->put(self::getCacheKey($key), $response, config('reply.expiration'));
    }

    public static function lock(string $key): Lock
    {
        $repository = self::store();
        $lockStore = $repository->getStore();
        if (! $lockStore instanceof LockProvider) {
            throw InvalidConfiguration::notALockProvider(config('reply.use'));
        }

        return $lockStore->lock(self::getLockKey($key));
    }

    public static function flush(): void
    {
        self::tagged()->flush();
    }

    protected static function store(): Repository
    {
        return Cache::store(config('reply.use'));
    }

    protected static function tagged(): TaggedCache
    {
        $repository = self::store();
        if (! $repository->getStore() instanceof TaggableStore) {
            throw InvalidConfiguration::notATaggableStore(config('reply.use'));
        }

        return $repository->tags('idempotency_requests');
    }

    protected static function getCacheKey(string $key): string
    {
        return trim($key);
    }

    protected static function getLockKey(string $key): string
    {
        return 'l:' . static::getCacheKey($key);
    }
}
