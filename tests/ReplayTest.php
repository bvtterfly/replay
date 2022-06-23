<?php

use Bvtterfly\Replay\Replay;
use Bvtterfly\Replay\Storage;
use Illuminate\Support\Facades\Route;
use function Pest\Laravel\get;
use function Pest\Laravel\post;

beforeEach(function () {
    config([
        'replay.use' => 'array',
    ]);
});

afterEach(function () {
    Storage::flush();
});

it('dont replay if package is disabled', function () {
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

it('dont replay post requests', function () {
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

it('replay idempotency successfully post requests', function () {
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

it('replay idempotency server errors post requests', function () {
    Route::post('resources', function () {
        return response('error '. uniqid(), 500, []);
    })->middleware([Replay::class]);

    $header = [
        config('replay.header_name') => uniqid(),
    ];
    $res = post('resources', [], $header);
    $res->assertStatus(500);
    $res->assertSee('error');
    $res2 = post('resources', [], $header);
    $res2->assertStatus(500);
    $res2->assertSee('error');
    expect($res)->getContent()->toEqual($res2->getContent());
});

it('dont replay idempotency client errors post requests', function () {
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

it('dont replay get requests', function () {
    Route::get('resources', function () {
        return response('resources '. uniqid(), 200, []);
    })->middleware([Replay::class]);

    $header = [
        config('replay.header_name') => uniqid(),
    ];
    $res = get('resources', [], $header);
    $res->assertStatus(200);
    $res->assertSee('resources');
    $res2 = get('resources', [], $header);
    $res2->assertStatus(200);
    $res2->assertSee('resources');
    expect($res)->getContent()->not()->toEqual($res2->getContent());
});

it('response conflict in progress idempotency post requests', function () {
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
