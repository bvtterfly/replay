<?php

use Bvtterfly\Replay\Contracts\Policy;
use Bvtterfly\Replay\Replay;
use Bvtterfly\Replay\Storage;
use Illuminate\Support\Facades\Route;
use Mockery\MockInterface;
use function Pest\Laravel\post;

beforeEach(function () {
    config([
        'replay.use' => 'array',
        'replay.policy' => Policy::class,
    ]);
});

afterEach(function () {
    Storage::flush();
});

it('dont replay if package is disabled', function () {
    app()->instance(Policy::class, Mockery::mock(Policy::class, function (MockInterface $mock) {
        $mock->shouldReceive('isIdempotentRequest')->andReturn(true);
        $mock->shouldReceive('isRecordableResponse')->andReturn(true);
    }));
    Route::post('resources', function () {
        return 'Created resource id :' . uniqid();
    })->middleware([Replay::class]);
    config([
        'replay.enabled' => false,
    ]);
    $header = [
        config('replay.header_name') => uniqid(),
    ];
    $res = post('resources', [], $header);
    $res->assertStatus(200);
    $res->assertSee('Created resource id');
    $res2 = post('resources', [], $header);
    $res2->assertStatus(200);
    $res2->assertSee('Created resource id');
    expect($res)->getContent()->not()->toEqual($res2->getContent());
});

it('dont replay if  requests is not idempotent', function () {
    app()->instance(Policy::class, Mockery::mock(Policy::class, function (MockInterface $mock) {
        $mock->shouldReceive('isIdempotentRequest')->andReturn(false);
        $mock->shouldReceive('isRecordableResponse')->andReturn(true);
    }));
    Route::post('resources', function () {
        return 'Created resource id :' . uniqid();
    })->middleware([Replay::class]);

    $res = post('resources', [], []);
    $res->assertStatus(200);
    $res->assertSee('Created resource id');
    $res2 = post('resources', [], []);
    $res2->assertStatus(200);
    $res2->assertSee('Created resource id');
    expect($res)->getContent()->not()->toEqual($res2->getContent());
});

it('replay idempotency requests', function () {
    app()->instance(Policy::class, Mockery::mock(Policy::class, function (MockInterface $mock) {
        $mock->shouldReceive('isIdempotentRequest')->andReturn(true);
        $mock->shouldReceive('isRecordableResponse')->andReturn(true);
    }));
    Route::post('resources', function () {
        return 'Created resource id :' . uniqid();
    })->middleware([Replay::class]);

    $header = [
        config('replay.header_name') => uniqid(),
    ];
    $res = post('resources', [], $header);
    $res->assertStatus(200);
    $res->assertSee('Created resource id');
    $res2 = post('resources', [], $header);
    $res2->assertStatus(200);
    $res2->assertSee('Created resource id');
    expect($res)->getContent()->toEqual($res2->getContent());
});


it('dont replay if responses is not idempotent.', function () {
    app()->instance(Policy::class, Mockery::mock(Policy::class, function (MockInterface $mock) {
        $mock->shouldReceive('isIdempotentRequest')->andReturn(true);
        $mock->shouldReceive('isRecordableResponse')->andReturn(false);
    }));
    Route::post('resources', function () {
        return response('error '. uniqid(), 400, []);
    })->middleware([Replay::class]);

    $header = [
        config('replay.header_name') => uniqid(),
    ];
    $res = post('resources', [], $header);
    $res->assertStatus(400);
    $res->assertSee('error');
    $res2 = post('resources', [], $header);
    $res2->assertStatus(400);
    $res2->assertSee('error');
    expect($res)->getContent()->not()->toEqual($res2->getContent());
});

it('response conflict in progress idempotency requests', function () {
    app()->instance(Policy::class, Mockery::mock(Policy::class, function (MockInterface $mock) {
        $mock->shouldReceive('isIdempotentRequest')->andReturn(true)->twice();
        $mock->shouldNotReceive('isIdempotentRequest');
    }));
    Route::post('resources', function () {
        return 'Created resource id :' . uniqid();
    })->middleware([Replay::class]);
    $key = uniqid();
    $key2 = uniqid();
    $lock = Storage::lock($key);
    $lock2 = Storage::lock($key2);
    $lock->get();
    $lock2->get();
    $header = [
        config('replay.header_name') => $key,
    ];
    $header2 = [
        config('replay.header_name') => $key2,
        'Accept' => 'application/json',
    ];
    $res = post('resources', [], $header);
    $res->assertStatus(409);
    $res->assertSee('An Error Occurred: Conflict');
    $res2 = post('resources', [], $header2);
    $res2->assertStatus(409);
    $res2->assertSee('already in progress');
    $lock->release();
    $lock2->release();
});
