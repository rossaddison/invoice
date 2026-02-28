<?php

declare(strict_types=1);

namespace App\Invoice\Helpers;

/**
 * Possibe future implementation: claude.com (free try) recommendation
 */

class RateLimitDetectorHelper
{
    /** @var array<string, list<int>> */
    private array $requestLog = [];

    private int $maxRequests;
    private int $windowSeconds;

    /** @var list<string> */
    private array $rateLimitHeaders = [
        'X-RateLimit-Limit',
        'X-RateLimit-Remaining',
        'X-RateLimit-Reset',
        'Retry-After',
        'X-Rate-Limit-Limit',
        'X-Rate-Limit-Remaining',
        'X-Rate-Limit-Reset',
        'RateLimit-Limit',
        'RateLimit-Remaining',
        'RateLimit-Reset',
    ];

    public function __construct(int $maxRequests = 100, int $windowSeconds = 60)
    {
        $this->maxRequests = $maxRequests;
        $this->windowSeconds = $windowSeconds;
    }

    /**
     * Check if a response indicates rate limiting based on HTTP status and headers.
     *
     * @param array<string, string|list<string>> $headers
     */
    public function isRateLimited(int $statusCode, array $headers): bool
    {
        if ($statusCode === 429) {
            return true;
        }

        if ($statusCode === 503 && $this->hasRetryAfterHeader($headers)) {
            return true;
        }

        if (in_array($statusCode, [420, 403], true)
                && $this->hasRateLimitHeaders($headers)) {
            return true;
        }

        return false;
    }

    /**
 * @param array<string, string|list<string>> $headers
 * @return array{limit: string|null, remaining: string|null, reset: string|null,
    retry_after: string|null, reset_in_seconds?: int}
 */
public function extractRateLimitInfo(array $headers): array
{
    $normalizedHeaders = $this->normalizeHeaders($headers);

    /** @var array<string, list<string>> $headerMap */
    $headerMap = [
        'limit'
            => ['x-ratelimit-limit', 'x-rate-limit-limit', 'ratelimit-limit'],
        'remaining'
            => ['x-ratelimit-remaining', 'x-rate-limit-remaining',
                'ratelimit-remaining'],
        'reset'
            => ['x-ratelimit-reset', 'x-rate-limit-reset', 'ratelimit-reset'],
        'retry_after' => ['retry-after'],
    ];

    $limit      = null;
    $remaining  = null;
    $reset      = null;
    $retryAfter = null;

    foreach ($headerMap['limit'] as $candidate) {
        if (isset($normalizedHeaders[$candidate])) {
            $limit = $normalizedHeaders[$candidate];
            break;
        }
    }

    foreach ($headerMap['remaining'] as $candidate) {
        if (isset($normalizedHeaders[$candidate])) {
            $remaining = $normalizedHeaders[$candidate];
            break;
        }
    }

    foreach ($headerMap['reset'] as $candidate) {
        if (isset($normalizedHeaders[$candidate])) {
            $reset = $normalizedHeaders[$candidate];
            break;
        }
    }

    foreach ($headerMap['retry_after'] as $candidate) {
        if (isset($normalizedHeaders[$candidate])) {
            $retryAfter = $normalizedHeaders[$candidate];
            break;
        }
    }

    if ($reset !== null) {
        return [
            'limit'            => $limit,
            'remaining'        => $remaining,
            'reset'            => $reset,
            'retry_after'      => $retryAfter,
            'reset_in_seconds' => $this->parseResetTime($reset),
        ];
    }

    return [
        'limit'       => $limit,
        'remaining'   => $remaining,
        'reset'       => $reset,
        'retry_after' => $retryAfter,
    ];
}

    /**
     * Track a request and check if we are approaching our own rate limit.
     *
     * @return array{count: int, remaining: int, limit: int, window_seconds: int,
        is_exceeded: bool, reset_at: int}
     */
    public function trackRequest(string $identifier = 'default'): array
    {
        $now = time();
        $windowStart = $now - $this->windowSeconds;

        $existing = $this->requestLog[$identifier] ?? [];

        $this->requestLog[$identifier] = array_values(
            array_filter($existing,
                    static fn(int $timestamp): bool => $timestamp > $windowStart)
        );

        $this->requestLog[$identifier][] = $now;

        $count = count($this->requestLog[$identifier]);
        $remaining = $this->maxRequests - $count;

        return [
            'count'          => $count,
            'remaining'      => max(0, $remaining),
            'limit'          => $this->maxRequests,
            'window_seconds' => $this->windowSeconds,
            'is_exceeded'    => $count > $this->maxRequests,
            'reset_at'       => $windowStart + $this->windowSeconds,
        ];
    }

    public function getRetryDelay(int $attempt, ?string $retryAfterHeader = null): int
    {
        if ($retryAfterHeader !== null) {
            return $this->parseRetryAfter($retryAfterHeader);
        }

        return min(64, 2 ** ($attempt - 1));
    }

    /**
     * Middleware-style wrapper: make a request with automatic retry on rate limit.
     *
     * @param callable(): array{status: int, headers: array<string, string|list<string>>,
        body: string} $requestFn
     * @return array{status: int, headers: array<string, string|list<string>>, body: string}
     */
    public function withRetry(callable $requestFn, int $maxAttempts = 5): array
    {
        $attempt = 0;

        while ($attempt < $maxAttempts) {
            $attempt++;

            /** @var array{status: int, headers: array<string, string|list<string>>,
                 body: string} $result */
            $result = $requestFn();

            if (!$this->isRateLimited($result['status'], $result['headers'])) {
                return $result;
            }

            if ($attempt >= $maxAttempts) {
                throw new \RuntimeException(
                    "Rate limit exceeded after {$maxAttempts} attempts."
                );
            }

            $rateLimitInfo = $this->extractRateLimitInfo($result['headers']);
            $delay = $this->getRetryDelay($attempt, $rateLimitInfo['retry_after']);

            echo "Rate limited. Retrying in {$delay}s (attempt {$attempt}/{$maxAttempts})...\n";
            sleep($delay);
        }

        throw new \RuntimeException('Unexpected retry loop exit.');
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    /**
     * @param array<string, string|list<string>> $headers
     */
    private function hasRetryAfterHeader(array $headers): bool
    {
        $normalized = $this->normalizeHeaders($headers);
        return isset($normalized['retry-after']);
    }

    /**
     * @param array<string, string|list<string>> $headers
     */
    private function hasRateLimitHeaders(array $headers): bool
    {
        $normalized = $this->normalizeHeaders($headers);
        $knownHeaders = array_map('strtolower', $this->rateLimitHeaders);
        return (bool) array_intersect(array_keys($normalized), $knownHeaders);
    }

    /**
     * @param array<string, string|list<string>> $headers
     * @return array<string, string>
     */
    private function normalizeHeaders(array $headers): array
    {
        $normalized = [];
        foreach ($headers as $key => $value) {
            $normalized[strtolower($key)] = is_array($value) ? $value[0] : $value;
        }
        return $normalized;
    }

    private function parseResetTime(string $value): int
    {
        if (is_numeric($value)) {
            $ts = (int) $value;
            return $ts > time() ? $ts - time() : $ts;
        }

        $ts = strtotime($value);
        return $ts !== false ? max(0, $ts - time()) : 0;
    }

    private function parseRetryAfter(string $value): int
    {
        if (is_numeric($value)) {
            return (int) $value;
        }

        $ts = strtotime($value);
        return $ts !== false ? max(0, $ts - time()) : 1;
    }
}
