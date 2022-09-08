<?php

declare(strict_types=1);

namespace Bvtterfly\Replay;

use Bvtterfly\Replay\Contracts\Policy;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class Replay
{
    public function __construct(
        private Policy $policy
    ) {
    }

    public function handle(Request $request, Closure $next, ?string $cachePrefix = null): Response
    {
        if (! config('replay.enabled')) {
            return $next($request);
        }

        if (! $this->policy->isIdempotentRequest($request)) {
            return $next($request);
        }

        $key = $this->getCacheKey($request, $cachePrefix);

        if ($recordedResponse = ReplayResponse::find($key)) {
            return $recordedResponse->toResponse(RequestHelper::signature($request));
        }
        $lock = Storage::lock($key);

        if (! $lock->get()) {
            abort(Response::HTTP_CONFLICT, __('replay::responses.error_messages.already_in_progress'));
        }

        try {
            $response = $next($request);
            if ($this->policy->isRecordableResponse($response)) {
                ReplayResponse::save($key, RequestHelper::signature($request), $response);
            }

            return $response;
        } finally {
            $lock->release();
        }
    }

    private function getCacheKey(Request $request, ?string $prefix = null): string
    {
        $idempotencyKey = $this->getIdempotencyKey($request);

        return $prefix ? "$prefix:$idempotencyKey" : $idempotencyKey;
    }

    private function getIdempotencyKey(Request $request): string
    {
        return $request->header(config('replay.header_name'));
    }
}
