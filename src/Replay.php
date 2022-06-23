<?php

namespace Bvtterfly\Replay;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\Response as StatusCode;

class Replay
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! config('replay.enabled')) {
            return $next($request);
        }

        if (! $request->isMethod('POST')) {
            return $next($request);
        }

        if (! ($key = $this->getIdempotencyKey($request))) {
            return $next($request);
        }

        if ($recordedResponse = ReplayResponse::find($key)) {
            return $recordedResponse->toResponse(
                $this->hashRequestParams($request)
            );
        }
        $lock = Storage::lock($key);

        if (! $lock->get()) {
            abort(StatusCode::HTTP_CONFLICT, 'An API request with the same Idempotency-Key is already in progress.');
        }

        try {
            $response = $next($request);
            if ($this->isResponseRecordable($response)) {
                ReplayResponse::save($key, $this->hashRequestParams($request), $response);
            }

            return $response;
        } finally {
            $lock->release();
        }
    }

    private function getIdempotencyKey(Request $request): string|null
    {
        return $request->header(config('replay.header_name'));
    }

    protected function hashRequestParams(Request $request): string
    {
        $params = json_encode(
            [
                $request->ip(),
                $request->path(),
                $request->all(),
                $request->headers->all(),
            ]
        );

        $hashAlgo = 'md5';

        if (in_array('xxh3', hash_algos())) {
            $hashAlgo = 'xxh3';
        }

        return hash($hashAlgo,   $params);
    }

    protected function isResponseRecordable(Response $response): bool
    {
        return $response->isSuccessful()
               || $response->isServerError();
    }
}
