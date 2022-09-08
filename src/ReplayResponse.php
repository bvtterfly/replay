<?php

declare(strict_types=1);

namespace Bvtterfly\Replay;

use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\Response as StatusCode;

class ReplayResponse
{
    public function __construct(
        public string $key,
        public string $requestSignature,
        public string $body,
        public int $status,
        public ?array $headers = []
    ) {
    }

    public static function fromResponse(string $key, string $requestSignature, Response $response): ReplayResponse
    {
        return new self(
            $key,
            $requestSignature,
            (string) $response->getContent(),
            $response->getStatusCode(),
            $response->headers->all()
        );
    }

    public function toResponse(string $requestSignature): Response
    {
        abort_if(
            $requestSignature !== $this->requestSignature,
            StatusCode::HTTP_CONFLICT,
            __('replay::responses.error_messages.mismatch')
        );

        return response($this->body, $this->status, $this->headers);
    }

    public static function find(string $key): ?ReplayResponse
    {
        return Storage::get($key);
    }

    public static function save(string $key, string $requestHash, Response $response): void
    {
        $replayResponse = ReplayResponse::fromResponse($key, $requestHash, $response);
        Storage::put($key, $replayResponse);
    }
}
