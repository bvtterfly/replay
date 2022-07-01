<?php

declare(strict_types=1);

namespace Bvtterfly\Replay;

use Bvtterfly\Replay\Contracts\Policy;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\Response as StatusCode;

class Replay
{
    private Policy $policy;

    public function __construct()
    {
        $this->policy = app()->make(config('replay.policy'));
    }

    public function handle(Request $request, Closure $next): Response
    {
        if (! config('replay.enabled')) {
            return $next($request);
        }

        if (! $this->policy->isIdempotentRequest($request)) {
            return $next($request);
        }

        $key = $this->getIdempotencyKey($request);

        if ($recordedResponse = ReplayResponse::find($key)) {
            return $recordedResponse->toResponse(RequestHelper::signature($request));
        }
        $lock = Storage::lock($key);

        if (! $lock->get()) {
            abort(StatusCode::HTTP_CONFLICT, __('replay::responses.error_messages.already_in_progress'));
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

    private function getIdempotencyKey(Request $request): string
    {
        return $request->header(config('replay.header_name'));
    }
}
