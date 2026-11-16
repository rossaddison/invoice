<?php

declare(strict_types=1);

namespace Tests\Testo\Service;

use App\Auth\Controller\AuthSecurityHelper;
use App\Service\ClientIpResolver;
use GuzzleHttp\Psr7\HttpFactory;
use Mockery as m;
use Psr\Log\LoggerInterface;
use Testo\Assert;
use Testo\Test;
use Yiisoft\Session\SessionInterface;
use Yiisoft\Yii\RateLimiter\CounterInterface;
use Yiisoft\Yii\RateLimiter\Storage\StorageInterface;

/**
 * Which IP a request is attributed to. Forwarding headers must only count when the TCP peer is a
 * trusted proxy; a visitor reaching the server directly must not be able to choose their own IP.
 *
 * @see ClientIpResolver
 */
#[Test]
final class ClientIpResolverTest
{
    private const string CLOUDFLARE_PEER = '172.67.10.20';

    /** @return list<string> */
    private function trusted(): array
    {
        /** @var list<string> $ranges */
        $ranges = require dirname(__DIR__, 3) . '/config/common/params.php';

        return $ranges['app.trustedProxies'];
    }

    private function resolver(): ClientIpResolver
    {
        return new ClientIpResolver($this->trusted());
    }

    /** @param array<string, string> $server */
    private function resolve(array $server, ?ClientIpResolver $resolver = null): ?string
    {
        return ($resolver ?? $this->resolver())->fromServerParams($server);
    }

    public function aDirectVisitorIsIdentifiedByTheirConnectionAddress(): void
    {
        Assert::same($this->resolve(['REMOTE_ADDR' => '198.51.100.7']), '198.51.100.7');
    }

    public function aDirectVisitorCannotChooseTheirIpWithCfConnectingIp(): void
    {
        Assert::same(
            $this->resolve(['REMOTE_ADDR' => '198.51.100.7', 'HTTP_CF_CONNECTING_IP' => '203.0.113.99']),
            '198.51.100.7',
        );
    }

    public function aDirectVisitorCannotChooseTheirIpWithXForwardedFor(): void
    {
        Assert::same(
            $this->resolve(['REMOTE_ADDR' => '198.51.100.7', 'HTTP_X_FORWARDED_FOR' => '203.0.113.99']),
            '198.51.100.7',
        );
    }

    public function xRealIpAndClientIpAreNeverBelieved(): void
    {
        Assert::same(
            $this->resolve([
                'REMOTE_ADDR' => self::CLOUDFLARE_PEER,
                'HTTP_X_REAL_IP' => '203.0.113.50',
                'HTTP_CLIENT_IP' => '203.0.113.51',
            ]),
            self::CLOUDFLARE_PEER,
        );
        Assert::same(
            $this->resolve(['REMOTE_ADDR' => '198.51.100.7', 'HTTP_X_REAL_IP' => '203.0.113.50']),
            '198.51.100.7',
        );
    }

    public function viaCloudflareTheCfConnectingIpIsBelieved(): void
    {
        Assert::same(
            $this->resolve(['REMOTE_ADDR' => self::CLOUDFLARE_PEER, 'HTTP_CF_CONNECTING_IP' => '203.0.113.99']),
            '203.0.113.99',
        );
    }

    public function viaCloudflareAnIpv6VisitorIsSeenAsThatAddress(): void
    {
        Assert::same(
            $this->resolve(['REMOTE_ADDR' => '2606:4700:4700::1111', 'HTTP_CF_CONNECTING_IP' => '2001:db8::7']),
            '2001:db8::7',
        );
    }

    public function viaCloudflareAGarbageCfConnectingIpFallsBackToThePeer(): void
    {
        Assert::same(
            $this->resolve(['REMOTE_ADDR' => self::CLOUDFLARE_PEER, 'HTTP_CF_CONNECTING_IP' => 'not-an-ip']),
            self::CLOUDFLARE_PEER,
        );
    }

    public function viaATrustedProxyTheRightmostUntrustedForwardedAddressIsUsed(): void
    {
        // the left-most entry is whatever the visitor claimed; the proxy appended the real one
        Assert::same(
            $this->resolve([
                'REMOTE_ADDR' => self::CLOUDFLARE_PEER,
                'HTTP_X_FORWARDED_FOR' => '1.1.1.1, 203.0.113.99',
            ]),
            '203.0.113.99',
        );
    }

    public function trustedProxiesInTheForwardedChainAreSkipped(): void
    {
        Assert::same(
            $this->resolve([
                'REMOTE_ADDR' => self::CLOUDFLARE_PEER,
                'HTTP_X_FORWARDED_FOR' => '203.0.113.99, 104.16.0.5',
            ]),
            '203.0.113.99',
        );
    }

    public function aMalformedForwardedEntryStopsTheWalkAndKeepsThePeer(): void
    {
        Assert::same(
            $this->resolve([
                'REMOTE_ADDR' => self::CLOUDFLARE_PEER,
                'HTTP_X_FORWARDED_FOR' => '203.0.113.99, garbage',
            ]),
            self::CLOUDFLARE_PEER,
        );
    }

    public function anEmptyTrustedListNeverBelievesAnyHeader(): void
    {
        $resolver = new ClientIpResolver([]);

        Assert::same(
            $this->resolve([
                'REMOTE_ADDR' => self::CLOUDFLARE_PEER,
                'HTTP_CF_CONNECTING_IP' => '203.0.113.99',
                'HTTP_X_FORWARDED_FOR' => '203.0.113.98',
            ], $resolver),
            self::CLOUDFLARE_PEER,
        );
    }

    public function rangeBoundariesAreExact(): void
    {
        $resolver = new ClientIpResolver(['173.245.48.0/20', '2a06:98c0::/29']);
        $headers = ['HTTP_CF_CONNECTING_IP' => '203.0.113.99'];

        // 173.245.48.0/20 covers 173.245.48.0 - 173.245.63.255
        Assert::same($this->resolve(['REMOTE_ADDR' => '173.245.48.0', ...$headers], $resolver), '203.0.113.99');
        Assert::same($this->resolve(['REMOTE_ADDR' => '173.245.63.255', ...$headers], $resolver), '203.0.113.99');
        Assert::same($this->resolve(['REMOTE_ADDR' => '173.245.64.0', ...$headers], $resolver), '173.245.64.0');
        Assert::same($this->resolve(['REMOTE_ADDR' => '173.245.47.255', ...$headers], $resolver), '173.245.47.255');
        // 2a06:98c0::/29 covers 2a06:98c0:: - 2a06:98c7:ffff:...
        Assert::same($this->resolve(['REMOTE_ADDR' => '2a06:98c7::1', ...$headers], $resolver), '203.0.113.99');
        Assert::same($this->resolve(['REMOTE_ADDR' => '2a06:98c8::1', ...$headers], $resolver), '2a06:98c8::1');
    }

    public function aSingleAddressCanBeTrustedWithoutAPrefix(): void
    {
        $resolver = new ClientIpResolver(['10.0.0.5']);

        Assert::same(
            $this->resolve(['REMOTE_ADDR' => '10.0.0.5', 'HTTP_CF_CONNECTING_IP' => '203.0.113.99'], $resolver),
            '203.0.113.99',
        );
        Assert::same(
            $this->resolve(['REMOTE_ADDR' => '10.0.0.6', 'HTTP_CF_CONNECTING_IP' => '203.0.113.99'], $resolver),
            '10.0.0.6',
        );
    }

    public function anIpv4MappedIpv6PeerMatchesAnIpv4Range(): void
    {
        $resolver = new ClientIpResolver(['173.245.48.0/20']);

        Assert::same(
            $this->resolve(['REMOTE_ADDR' => '::ffff:173.245.48.9', 'HTTP_CF_CONNECTING_IP' => '203.0.113.99'], $resolver),
            '203.0.113.99',
        );
    }

    public function aBrokenRangeInTheListTrustsNothingAndDoesNotThrow(): void
    {
        $resolver = new ClientIpResolver(['not-a-range', '10.0.0.0/99', '10.0.0.0/abc', '']);

        Assert::same(
            $this->resolve(['REMOTE_ADDR' => '10.0.0.5', 'HTTP_CF_CONNECTING_IP' => '203.0.113.99'], $resolver),
            '10.0.0.5',
        );
    }

    public function aMissingOrInvalidConnectionAddressGivesNull(): void
    {
        Assert::same($this->resolve([]), null);
        Assert::same($this->resolve(['REMOTE_ADDR' => 'nope', 'HTTP_CF_CONNECTING_IP' => '203.0.113.99']), null);
    }

    public function theConfiguredListIsCloudflaresPublishedRanges(): void
    {
        $ranges = $this->trusted();

        Assert::true(in_array('173.245.48.0/20', $ranges, true));
        Assert::true(in_array('104.16.0.0/13', $ranges, true));
        Assert::true(in_array('2606:4700::/32', $ranges, true));
        Assert::same(count($ranges), 22);
    }

    public function theAuthSecurityHelperUsesTheResolverForTheTwoFactorAndResetLimiters(): void
    {
        $helper = new AuthSecurityHelper(
            m::mock(CounterInterface::class),
            m::mock(StorageInterface::class),
            m::mock(LoggerInterface::class),
            m::mock(SessionInterface::class),
            $this->resolver(),
        );
        $spoofed = new HttpFactory()->createServerRequest('POST', 'https://example.test/en/verifyLogin', [
            'REMOTE_ADDR' => '198.51.100.7',
            'HTTP_CF_CONNECTING_IP' => '203.0.113.1',
            'HTTP_X_FORWARDED_FOR' => '203.0.113.2',
            'HTTP_X_REAL_IP' => '203.0.113.3',
            'HTTP_CLIENT_IP' => '203.0.113.4',
        ]);

        Assert::same($helper->getClientIpAddress($spoofed), '198.51.100.7');
    }
}
