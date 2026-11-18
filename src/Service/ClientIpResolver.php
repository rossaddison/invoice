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

        // Cloudflare sets its header itself, replacing whatever the client sent; failing that, the
        // client is the first address in X-Forwarded-For that is not one of our own proxies; failing
        // that, the proxy itself is all we know.
        return $this->validIp($server['HTTP_CF_CONNECTING_IP'] ?? null)
            ?? $this->firstUntrustedFromTheRight($server['HTTP_X_FORWARDED_FOR'] ?? null)
            ?? $remote;
    }

    /**
     * Walks X-Forwarded-For from the right, the end our own proxies append to, and takes the first address
     * that is not itself a trusted proxy. Entries further left are client-supplied and are not believed. A
     * malformed entry stops the walk.
     */
    private function firstUntrustedFromTheRight(mixed $forwarded): ?string
    {
        if (!is_string($forwarded) || $forwarded === '') {
            return null;
        }
        foreach (array_reverse(explode(',', $forwarded)) as $entry) {
            $ip = $this->validIp($entry);
            if ($ip === null) {
                break;
            }
            if (!$this->isTrusted($ip)) {
                return $ip;
            }
        }
        return null;
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
        $bits = $this->prefixLength($parts[1] ?? null, strlen((string) $address) * 8);
        if ($address === null || $subnet === null || strlen($address) !== strlen($subnet) || $bits === null) {
            return false;
        }
        return $this->samePrefix($address, $subnet, $bits);
    }

    /**
     * @param string|null $given the part after the slash, null when the range has none (a single address)
     * @param int $maxBits the length of the address in bits: 32 or 128
     *
     * @return int|null the prefix length, or null when it is not a whole number or is longer than the address
     */
    private function prefixLength(?string $given, int $maxBits): ?int
    {
        if ($given === null) {
            return $maxBits;
        }
        return ctype_digit($given) && (int) $given <= $maxBits ? (int) $given : null;
    }

    /** Whether the first $bits bits of the two packed addresses are the same. */
    private function samePrefix(string $address, string $subnet, int $bits): bool
    {
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
