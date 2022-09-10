<?php

declare(strict_types=1);

namespace Bvtterfly\Replay;

use Bvtterfly\Replay\Exceptions\InvalidConfiguration;
use Illuminate\Cache\TaggableStore;
use Illuminate\Cache\TaggedCache;
use Illuminate\Contracts\Cache\Lock;
use Illuminate\Contracts\Cache\LockProvider;
use Illuminate\Contracts\Cache\Repository;

class Storage
{
    private TaggedCache $taggedCache;

    private LockProvider $lockProvider;

    public function __construct(
        private Repository $cache,
    ) {
        $this->taggedCache = $this->tagged();
        $this->lockProvider = $this->lockProvider();
    }

    public function get(string $key): ?ReplayResponse
    {
        return $this->taggedCache->get($this->getCacheKey($key));
    }

    public function put(string $key, ReplayResponse $response): void
    {
        $this->taggedCache->put($this->getCacheKey($key), $response, config('replay.expiration'));
    }

    public function lock(string $key): Lock
    {
        return $this->lockProvider->lock(self::getLockKey($key));
    }

    public function flush(): bool
    {
        return $this->taggedCache->flush();
    }

    protected function lockProvider(): LockProvider
    {
        $lockProvider = $this->cache->getStore();
        if (! $lockProvider instanceof LockProvider) {
            throw InvalidConfiguration::notALockProvider(config('replay.use'));
        }

        return $lockProvider;
    }

    protected function tagged(): TaggedCache
    {
        if (! $this->cache->getStore() instanceof TaggableStore) {
            throw InvalidConfiguration::notATaggableStore(config('replay.use'));
        }

        return $this->cache->tags('idempotency_requests');
    }

    protected function getCacheKey(string $key): string
    {
        return trim($key);
    }

    protected function getLockKey(string $key): string
    {
        return 'l:'.$this->getCacheKey($key);
    }
}
