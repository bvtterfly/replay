<?php

use Bvtterfly\Replay\StripePolicy;

it('should return true when request method is post and has Idempotency header', function () {
    $req = request();
    $req->setMethod('POST');
    $req->headers->set(config('replay.header_name'), uniqid());
    expect(new StripePolicy())->isIdempotentRequest($req)->toBeTrue();
});

it('should return false when request method is not post', function () {
    $req = request();
    $req->setMethod('GET');
    $req->headers->set(config('replay.header_name'), uniqid());
    expect(new StripePolicy())->isIdempotentRequest($req)->toBeFalse();
});

it('should return false when request method is post but missing header', function () {
    $req = request();
    $req->setMethod('POST');
    expect(new StripePolicy())->isIdempotentRequest($req)->toBeFalse();
});

test('successful response is a recordable response', function ($status) {
    expect(new StripePolicy())->isRecordableResponse(response('', $status))->toBeTrue();
})->with([200, 201, 202, 203, 204, 205, 206]);

test('server error response is a recordable response', function ($status) {
    expect(new StripePolicy())->isRecordableResponse(response('', $status))->toBeTrue();
})->with([500, 501, 502, 503, 504, 505, 506, 510]);

test('client error response isn\'t a recordable response', function ($status) {
    expect(new StripePolicy())->isRecordableResponse(response('', $status))->toBeFalse();
})->with([400, 401, 402, 403, 404, 405, 406, 422]);

test('redirect responses aren\'t recordable response', function ($status) {
    expect(new StripePolicy())->isRecordableResponse(response('', $status))->toBeFalse();
})->with([300, 301, 302, 303, 304]);
