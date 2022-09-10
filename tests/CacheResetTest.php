<?php

use Bvtterfly\Replay\Commands\CacheReset;
use Illuminate\Support\Facades\Cache;
use function Pest\Laravel\artisan;

it('can flush cache', function () {
    $this->app['config']->set('replay.use', 'array');
    Cache::store('array')->tags('idempotency_requests')->put('test', 'value');
    Cache::store('array')->tags('idempotency_requests')->put('test-2', 'value-2');
    artisan(CacheReset::class)->assertSuccessful();
    expect(
        Cache::store('array')->tags('idempotency_requests')
    )->has('test')
     ->toBeFalse()
     ->has('test-2')
     ->toBeFalse();
});
