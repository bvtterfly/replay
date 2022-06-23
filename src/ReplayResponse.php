<?php

namespace Bvtterfly\Replay;

use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\Response as StatusCode;

class ReplayResponse
{
    public function __construct(
        public string $key,
        public string $requestHash,
        public string $body,
        public int $status,
        public ?array $headers = []
    ) {
    }

    public static function fromResponse(string $key, string $requestHash, Response $response): ReplayResponse
    {
        return new self(
            $key,
            $requestHash,
            (string) $response->getContent(),
            $response->getStatusCode(),
            $response->headers->all()
        );
    }

    public function toResponse(string $requestHash): Response
    {
        if ($requestHash !== $this->requestHash) {
            abort(
                StatusCode::HTTP_CONFLICT,
                'There was a mismatch between this request\'s parameters and the ' .
                        'parameters of a previously stored request with the same ' .
                        'Idempotency-Key.'
            );
        }

        return response($this->body, $this->status, $this->headers);
    }

    public static function find(string $key): ?ReplayResponse
    {
        return Storage::get($key);
    }

    public static function save(string $key, string $requestHash, Response $response): void
    {
        $replyResponse = ReplayResponse::fromResponse($key, $requestHash, $response);
        Storage::put($key, $replyResponse);
    }
}
