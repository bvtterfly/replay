<?php

use Bvtterfly\Replay\Exceptions\InvalidConfiguration;
use Bvtterfly\Replay\Facades\Storage;
use Bvtterfly\Replay\ReplayResponse;
use Illuminate\Support\Facades\Cache;

it('must throw an exception if cache store doesn\'t support tagging', function ($store) {
    $this->app['config']->set('replay.use', $store);
    expect(fn () => Storage::get('test'))->toThrow(InvalidConfiguration::class, "Configured cache store `{$store}` does not support Tagging.");
})->with([
    'file',
    'database',
]);

it('can get an item from cache within a tag', function () {
    $this->app['config']->set('replay.use', 'array');
    $resp = new ReplayResponse('test', 'hash', '', 200);
    Cache::store('array')->tags('idempotency_requests')->put('test', $resp);
    expect(Storage::get('test'))->toEqual($resp);
});

it('can save an item into a cache store within a tag', function () {
    $this->app['config']->set('replay.use', 'array');
    $resp = new ReplayResponse('test', 'hash', '', 200);
    Storage::put('test', $resp);
    expect(
        Cache::store('array')->tags('idempotency_requests')->get('test')
    )->toEqual($resp)
     ->and(Cache::store('array')->has('test'))->toBeFalse();
});

it('can get a lock from a cache store', function () {
    $this->app['config']->set('replay.use', 'array');
    $lock = Storage::lock('test');
    expect(
        $lock
    )->get()->toBeTrue();
});

it('can flush all items from a cache within a tag', function () {
    $this->app['config']->set('replay.use', 'array');
    Cache::store('array')->tags('idempotency_requests')->put('test', 'value');
    Cache::store('array')->tags('idempotency_requests')->put('test-2', 'value-2');
    Cache::store('array')->put('test-3', 'value-3');
    Storage::flush();
    expect(
        Cache::store('array')->tags('idempotency_requests')
    )->has('test')
     ->toBeFalse()
     ->has('test-2')
     ->toBeFalse()
     ->and(Cache::store('array'))
     ->has('test-3')
     ->toBeTrue();
});
