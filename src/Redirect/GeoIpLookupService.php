<?php

declare(strict_types=1);

namespace App\Redirect;

use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Log\LoggerInterface;

/**
 * Resolves a visitor's country from their IP address via a free,
 * no-API-key-required lookup — the same class of trade-off every
 * country-level geo-IP feature has: either send the IP to a third party
 * for lookup, or ship and maintain a local MaxMind-style database (which
 * itself requires a free account/license key to download). Chosen here
 * since this is a small, non-critical feature — never blocks or breaks
 * the actual redirect it's used from (`RedirectController::go()`) on
 * any failure, and never persists the IP address itself anywhere, only
 * the resolved country.
 *
 * Tries `ipapi.co` first, falling back to `ip-api.com` (a genuinely
 * different provider, not just a similarly-named one) on any failure —
 * confirmed live 2026-08-18: `ipapi.co` returned `429 Too Many Requests`
 * on this app's very first real production lookup, before any
 * meaningful volume had been sent at all, which points at this server's
 * own source IP already carrying rate-limit history with that specific
 * provider (shared hosting IPs sometimes inherit a previous tenant's
 * usage) rather than a real capacity problem — a single independent
 * fallback is cheap insurance against that. `ip-api.com`'s free tier is
 * HTTP-only (no HTTPS without a paid plan) — accepted here since only a
 * bare IP address is ever sent, nothing sensitive, and only as the
 * fallback, not the primary.
 *
 * Deliberately fails closed to null (never throws) on any error —
 * network failure, timeout, private/loopback IP, unexpected response
 * shape, or both providers failing — since a broken geo-IP lookup must
 * never be allowed to break the redirect it's a side effect of.
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

        return $this->lookupViaIpapiCo($ip) ?? $this->lookupViaIpApiCom($ip);
    }

    /**
     * `ipapi.co/{ip}/country/` — a plain-text response of the ISO
     * 3166-1 alpha-2 code.
     */
    private function lookupViaIpapiCo(string $ip): ?string
    {
        try {
            $response = $this->httpClient->get('https://ipapi.co/' . rawurlencode($ip) . '/country/');
            $code = strtolower(trim((string) $response->getBody()));
        } catch (GuzzleException $e) {
            $this->logger->debug('GeoIpLookupService: ipapi.co lookup failed.', ['error' => $e->getMessage()]);
            return null;
        }

        // A 2-letter alpha response is the only shape this endpoint ever
        // returns for a successful lookup; anything else (an HTML error
        // page, a rate-limit message, "Undefined") means no real answer.
        return preg_match('/^[a-z]{2}$/', $code) === 1 ? $code : null;
    }

    /**
     * `ip-api.com/json/{ip}?fields=status,countryCode` — a JSON body,
     * `{"status":"success","countryCode":"GB"}` on success or
     * `{"status":"fail","message":"..."}` on failure.
     */
    private function lookupViaIpApiCom(string $ip): ?string
    {
        try {
            $response = $this->httpClient->get(
                'http://ip-api.com/json/' . rawurlencode($ip) . '?fields=status,countryCode',
            );
            /** @var array{status?: string, countryCode?: string} $data */
            $data = json_decode((string) $response->getBody(), true, 512, JSON_THROW_ON_ERROR);
        } catch (GuzzleException|\JsonException $e) {
            $this->logger->debug('GeoIpLookupService: ip-api.com lookup failed.', ['error' => $e->getMessage()]);
            return null;
        }

        if (($data['status'] ?? '') !== 'success') {
            return null;
        }

        $code = strtolower($data['countryCode'] ?? '');
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
