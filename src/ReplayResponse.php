<?php

declare(strict_types=1);

namespace Bvtterfly\Replay;

use Symfony\Component\HttpFoundation\Response;

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
            Response::HTTP_CONFLICT,
            __('replay::responses.error_messages.mismatch')
        );

        $response = response($this->body, $this->status, $this->headers);
        if (! blank($configKey = config('replay.replied_header_name'))) {
            $response->header($configKey, 'true');
        }

        return $response;
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
