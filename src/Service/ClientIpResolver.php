<?php

declare(strict_types=1);

namespace App\Service;

use Psr\Http\Message\ServerRequestInterface;

/**
 * The one place that decides which IP address a request "comes from" for rate limiting and Turnstile.
 *
 * Forwarding headers (CF-Connecting-IP, X-Forwarded-For) are plain request headers: anyone who can
 * reach the server directly can send any value in them. They are therefore only believed when the
 * TCP peer itself (REMOTE_ADDR) is one of the configured trusted proxies (see params
 * `app.trustedProxies`). Every other peer is identified by REMOTE_ADDR alone. With an empty list,
 * headers are never believed.
 *
 * X-Real-IP and Client-IP are never used: no proxy in front of this app sets them reliably.
 */
final readonly class ClientIpResolver
{
    /** @param list<string> $trustedProxies IPs or CIDR ranges, IPv4 or IPv6 */
    public function __construct(private array $trustedProxies = [])
    {
    }

    public function resolve(ServerRequestInterface $request): ?string
    {
        return $this->fromServerParams($request->getServerParams());
    }

    /**
     * @param array<array-key, mixed> $server
     * @return string|null null when REMOTE_ADDR is missing or not an IP address
     */
    public function fromServerParams(array $server): ?string
    {
        $remote = $this->validIp($server['REMOTE_ADDR'] ?? null);
        if ($remote === null || !$this->isTrusted($remote)) {
            return $remote;
        }

        // Cloudflare sets this header itself, replacing whatever the client sent.
        $cloudflare = $this->validIp($server['HTTP_CF_CONNECTING_IP'] ?? null);
        if ($cloudflare !== null) {
            return $cloudflare;
        }

        // Otherwise walk X-Forwarded-For from the right, the end our own proxies append to,
        // and take the first address that is not itself a trusted proxy. Entries further left are
        // client-supplied and are not believed. A malformed entry stops the walk.
        /** @var mixed $forwarded */
        $forwarded = $server['HTTP_X_FORWARDED_FOR'] ?? null;
        if (is_string($forwarded) && $forwarded !== '') {
            foreach (array_reverse(explode(',', $forwarded)) as $entry) {
                $ip = $this->validIp($entry);
                if ($ip === null) {
                    break;
                }
                if (!$this->isTrusted($ip)) {
                    return $ip;
                }
            }
        }
        return $remote;
    }

    private function validIp(mixed $value): ?string
    {
        if (!is_string($value)) {
            return null;
        }
        $ip = trim($value);
        return filter_var($ip, FILTER_VALIDATE_IP) === false ? null : $ip;
    }

    private function isTrusted(string $ip): bool
    {
        foreach ($this->trustedProxies as $range) {
            if ($this->inRange($ip, $range)) {
                return true;
            }
        }
        return false;
    }

    private function inRange(string $ip, string $range): bool
    {
        $parts = explode('/', $range, 2);
        $address = $this->toBinary($ip);
        $subnet = $this->toBinary($parts[0]);
        if ($address === null || $subnet === null || strlen($address) !== strlen($subnet)) {
            return false;
        }
        $maxBits = strlen($address) * 8;
        $bits = isset($parts[1]) ? (int) $parts[1] : $maxBits;
        if ($bits < 0 || $bits > $maxBits || (isset($parts[1]) && !ctype_digit($parts[1]))) {
            return false;
        }
        $wholeBytes = intdiv($bits, 8);
        if (substr($address, 0, $wholeBytes) !== substr($subnet, 0, $wholeBytes)) {
            return false;
        }
        $remainder = $bits % 8;
        if ($remainder === 0) {
            return true;
        }
        $mask = (0xFF << (8 - $remainder)) & 0xFF;
        return (ord($address[$wholeBytes]) & $mask) === (ord($subnet[$wholeBytes]) & $mask);
    }

    /** Packed address; an IPv4-mapped IPv6 address (::ffff:a.b.c.d) is reduced to its IPv4 form. */
    private function toBinary(string $ip): ?string
    {
        $packed = @inet_pton($ip);
        if ($packed === false) {
            return null;
        }
        if (strlen($packed) === 16 && str_starts_with($packed, "\0\0\0\0\0\0\0\0\0\0\xFF\xFF")) {
            return substr($packed, 12);
        }
        return $packed;
    }
}
