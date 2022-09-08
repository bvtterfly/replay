<?php

use Bvtterfly\Replay\ReplayResponse;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpKernel\Exception\HttpException;

it('can create from response', function () {
    $res = response('test', 200, ['header-1' => 'test']);
    $replayResponse = ReplayResponse::fromResponse('test', 'hash', $res);
    expect($replayResponse)->toEqual(new ReplayResponse('test', 'hash', 'test', 200, $res->headers->all()));
});

it('can convert to a response with replied header', function () {
    $replayResponse = new ReplayResponse('test', 'hash', 'test', 200, ['test-header' => 'value']);
    expect($replayResponse)->toResponse('hash')->toEqual(response('test', 200, [
        'test-header' => 'value',
        'Idempotent-Replayed' => 'true',
    ]));
});

it('can convert to a response with custom replied header', function () {
    config([
        'replay.replied_header_name' => 'Replayed-Test',
    ]);
    $replayResponse = new ReplayResponse('test', 'hash', 'test', 200, ['test-header' => 'value']);
    expect($replayResponse)->toResponse('hash')->toEqual(response('test', 200, [
        'test-header' => 'value',
        'Replayed-Test' => 'true',
    ]));
});

it('can convert to a response without replied header if it\'s empty', function () {
    config([
        'replay.replied_header_name' => '',
    ]);
    $replayResponse = new ReplayResponse('test', 'hash', 'test', 200, ['test-header' => 'value']);
    expect($replayResponse)->toResponse('hash')->headers->not->toHaveKey('Idempotent-Replayed');
});

it('throw an exception if request hash is different', function () {
    $replayResponse = new ReplayResponse('test', 'hash', 'test', 200, ['test-header' => 'value']);
    expect($replayResponse)->toResponse('hash-2');
})->throws(HttpException::class);

it('can find a replay response from cache store', function () {
    $this->app['config']->set('replay.use', 'array');
    $resp = new ReplayResponse('test', 'hash', '', 200);
    Cache::store('array')->tags('idempotency_requests')->put('test', $resp);
    expect(ReplayResponse::find('test'))->toEqual($resp);
});

it('can save a response to cache store', function () {
    $this->app['config']->set('replay.use', 'array');
    $res = response('test', 200, ['header-1' => 'test']);
    ReplayResponse::save('test', 'hash', $res);
    expect(ReplayResponse::find('test'))->toEqual(new ReplayResponse('test', 'hash', 'test', 200, $res->headers->all()));
});
