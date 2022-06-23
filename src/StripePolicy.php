<?php

declare(strict_types=1);

namespace Bvtterfly\Replay;

use Illuminate\Http\Request;
use Illuminate\Http\Response;

class StripePolicy implements Contracts\Policy
{
    public function isIdempotentRequest(Request $request): bool
    {
        return $request->isMethod('POST')
               && $request->hasHeader(config('replay.header_name'));
    }

    public function isRecordableResponse(Response $response): bool
    {
        return $response->isSuccessful()
               || $response->isServerError();
    }
}
