<?php

declare(strict_types=1);

namespace App\Invoice\PaymentInformation\Service;

/**
 * YooKassa's webhook notifications carry no cryptographic signature at all —
 * ground-truthed directly from the official (now-archived)
 * `yoomoney/yookassa-sdk-php` SDK's `YooKassa\Helpers\SecurityHelper` class:
 * the only authenticity mechanism YooKassa itself documents is an IP
 * allowlist. The exact CIDR ranges below are copied verbatim from that
 * class, not guessed.
 *
 * IP-allowlisting alone is not tamper-proof (a shared-hosting reverse proxy
 * can make `REMOTE_ADDR` unreliable, and IPs can in principle be spoofed at
 * the network level) — this is why YookassaWebhookHandler treats a passing
 * IP check as only a fast pre-filter, and always re-confirms the actual
 * payment/refund state via an authenticated GET back to YooKassa's own API
 * before ever marking an invoice paid, rather than trusting the notification
 * body's own `object.status` field.
 */
final class YookassaWebhookIpVerifier
{
    // These are YooKassa's own published webhook-sender IP ranges
    // (ground-truthed from their SDK's SecurityHelper class) — an
    // intentional third-party allowlist, not accidentally-hardcoded internal
    // infrastructure. NOSONAR: php:S1313 on each literal below.
    /** @var list<string> CIDR ranges or bare IPv4 addresses. */
    private const array IPV4_TRUSTED = [
        '185.71.76.0/27', // NOSONAR: php:S1313
        '185.71.77.0/27', // NOSONAR: php:S1313
        '77.75.153.0/25', // NOSONAR: php:S1313
        '77.75.154.128/25', // NOSONAR: php:S1313
        '77.75.156.11', // NOSONAR: php:S1313
        '77.75.156.35', // NOSONAR: php:S1313
    ];

    /** @var list<array{0: string, 1: string}> [firstInRange, lastInRange] IPv6 pairs. */
    private const array IPV6_TRUSTED = [
        ['2a02:5180:0000:1509:0000:0000:0000:0000', '2a02:5180:0000:1509:ffff:ffff:ffff:ffff'], // NOSONAR: php:S1313
        ['2a02:5180:0000:2655:0000:0000:0000:0000', '2a02:5180:0000:2655:ffff:ffff:ffff:ffff'], // NOSONAR: php:S1313
        ['2a02:5180:0000:1533:0000:0000:0000:0000', '2a02:5180:0000:1533:ffff:ffff:ffff:ffff'], // NOSONAR: php:S1313
        ['2a02:5180:0000:2669:0000:0000:0000:0000', '2a02:5180:0000:2669:ffff:ffff:ffff:ffff'], // NOSONAR: php:S1313
    ];

    public function isTrusted(string $ip): bool
    {
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) !== false) {
            return $this->isInIpv6TrustedList($ip);
        }
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) !== false) {
            return $this->isInIpv4TrustedList($ip);
        }
        return false;
    }

    private function isInIpv4TrustedList(string $ip): bool
    {
        foreach (self::IPV4_TRUSTED as $range) {
            if ($this->isIpInV4Range($ip, $range)) {
                return true;
            }
        }
        return false;
    }

    private function isIpInV4Range(string $ip, string $range): bool
    {
        if (!str_contains($range, '/')) {
            return ip2long($ip) === ip2long($range);
        }
        $parts = explode('/', $range, 2);
        if (count($parts) !== 2) {
            return false;
        }
        [$rangeIp, $netmaskBits] = $parts;

        $ipDec = ip2long($ip);
        $rangeDec = ip2long($rangeIp);
        $wildcardDec = (int) (2 ** (32 - (int) $netmaskBits)) - 1;
        $netmaskDec = ~$wildcardDec;

        return $ipDec !== false && $rangeDec !== false
            && ($ipDec & $netmaskDec) === ($rangeDec & $netmaskDec);
    }

    private function isInIpv6TrustedList(string $ip): bool
    {
        $ipBinary = inet_pton($ip);
        if ($ipBinary === false) {
            return false;
        }

        foreach (self::IPV6_TRUSTED as [$first, $last]) {
            $firstBinary = inet_pton($first);
            $lastBinary = inet_pton($last);
            if ($firstBinary === false || $lastBinary === false) {
                continue;
            }
            if (strlen($ipBinary) === strlen($firstBinary) && $ipBinary >= $firstBinary && $ipBinary <= $lastBinary) {
                return true;
            }
        }
        return false;
    }
}
