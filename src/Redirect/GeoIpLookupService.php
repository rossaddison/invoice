<?php

declare(strict_types=1);

namespace App\Redirect;

use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Log\LoggerInterface;

/**
 * Resolves a visitor's country from their IP address via a free,
 * no-API-key-required lookup (`ipapi.co/{ip}/country/`, a plain-text
 * response of the ISO 3166-1 alpha-2 code) — the same class of trade-off
 * every country-level geo-IP feature has: either send the IP to a third
 * party for lookup, or ship and maintain a local MaxMind-style database
 * (which itself requires a free account/license key to download). Chosen
 * here since this is a small, non-critical feature — never blocks or
 * breaks the actual redirect it's used from (`RedirectController::go()`)
 * on any failure, and never persists the IP address itself anywhere,
 * only the resolved country.
 *
 * Deliberately fails closed to null (never throws) on any error —
 * network failure, timeout, private/loopback IP, unexpected response
 * shape — since a broken geo-IP lookup must never be allowed to break
 * the redirect it's a side effect of.
 */
final class GeoIpLookupService
{
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly HttpClient $httpClient = new HttpClient(['timeout' => 2.0]),
    ) {
    }

    public function lookupCountryCode(string $ip): ?string
    {
        if (!$this->isPubliclyRoutable($ip)) {
            return null;
        }

        try {
            $response = $this->httpClient->get('https://ipapi.co/' . rawurlencode($ip) . '/country/');
            $code = strtolower(trim((string) $response->getBody()));
        } catch (GuzzleException $e) {
            $this->logger->debug('GeoIpLookupService: lookup failed.', ['error' => $e->getMessage()]);
            return null;
        }

        // A 2-letter alpha response is the only shape this endpoint ever
        // returns for a successful lookup; anything else (an HTML error
        // page, a rate-limit message, "Undefined") means no real answer.
        return preg_match('/^[a-z]{2}$/', $code) === 1 ? $code : null;
    }

    private function isPubliclyRoutable(string $ip): bool
    {
        return filter_var(
            $ip,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE,
        ) !== false;
    }
}
