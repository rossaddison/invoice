<?php

declare(strict_types=1);

namespace App\Invoice\As4;

/**
 * Immutable value object carrying a raw AS4 HTTP response.
 *
 * @psalm-suppress UnusedClass
 */
final readonly class As4HttpResponse
{
    /** HTTP status codes for which the send should be retried. */
    private const array RETRIABLE_CODES = [408, 429, 500, 502, 503, 504];

    public function __construct(
        /** HTTP status code returned by the AS4 endpoint. */
        public int $statusCode,
        /** Raw response body (may contain an ebMS3 receipt or error signal). */
        public string $body,
        /** Value of the HTTP Content-Type response header (used for MIME boundary detection). */
        public string $contentType = '',
    ) {}

    /** Returns true for HTTP 200 or 202 (synchronous or async acceptance). */
    public function isSuccess(): bool
    {
        return $this->statusCode === 200 || $this->statusCode === 202;
    }

    /** Returns true when the endpoint is temporarily unavailable and a retry is warranted. */
    public function isRetriable(): bool
    {
        return in_array($this->statusCode, self::RETRIABLE_CODES, true);
    }
}
