<?php

declare(strict_types=1);

namespace Bvtterfly\Replay\Contracts;

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

interface Policy
{
    public function isIdempotentRequest(Request $request): bool;

    public function isRecordableResponse(Response $response): bool;
}
