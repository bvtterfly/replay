<?php

declare(strict_types=1);

namespace Bvtterfly\Replay\Contracts;

use Illuminate\Http\Request;
use Illuminate\Http\Response;

interface Policy
{
    public function isIdempotentRequest(Request $request): bool;

    public function isRecordableResponse(Response $response): bool;
}
