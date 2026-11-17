<?php

declare(strict_types=1);

namespace Tests\Testo\Middleware;

use App\Middleware\RateLimiter;
use App\Service\ClientIpResolver;
use GuzzleHttp\Psr7\HttpFactory;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Testo\Assert;
use Testo\Test;
use Yiisoft\Http\Status;
use Yiisoft\Yii\RateLimiter\Storage\StorageInterface;

/**
 * Drives the real RateLimiter::perIp()/global() closures against an in-memory counter store,
 * the way a burst of POSTs would hit the signup routes.
 *
 * @see RateLimiter
 */
#[Test]
final class RateLimiterTest
{
    private function makeStorage(): StorageInterface
    {
        return new class () implements StorageInterface {
            /** @var array<string, float> */
            private array $values = [];

            #[\Override]
            public function saveIfNotExists(string $key, float $value, int $ttl): bool
            {
                if (isset($this->values[$key])) {
                    return false;
                }
                $this->values[$key] = $value;
                return true;
            }

            #[\Override]
            public function saveCompareAndSwap(string $key, float $oldValue, float $newValue, int $ttl): bool
            {
                if (!isset($this->values[$key]) || $this->values[$key] !== $oldValue) {
                    return false;
                }
                $this->values[$key] = $newValue;
                return true;
            }

            #[\Override]
            public function get(string $key): ?float
            {
                return $this->values[$key] ?? null;
            }
        };
    }

    private function okHandler(): RequestHandlerInterface
    {
        return new class () implements RequestHandlerInterface {
            #[\Override]
            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                return new HttpFactory()->createResponse(Status::OK);
            }
        };
    }

    /** @param array<string, string> $server */
    private function post(array $server): ServerRequestInterface
    {
        return new HttpFactory()->createServerRequest('POST', 'https://example.test/en/homecare-signup', $server);
    }

    /** @param list<array<string, string>> $servers one POST per entry; returns how many were let through */
    private function countAllowed(MiddlewareInterface $middleware, array $servers): int
    {
        $allowed = 0;
        foreach ($servers as $server) {
            $response = $middleware->process($this->post($server), $this->okHandler());
            if ($response->getStatusCode() === Status::OK) {
                $allowed++;
            }
        }
        return $allowed;
    }

    private function perIp(int $limit): MiddlewareInterface
    {
        /** @var array{'app.trustedProxies': list<string>} $params */
        $params = require dirname(__DIR__, 3) . '/config/common/params.php';

        /** @var MiddlewareInterface */
        return (RateLimiter::perIp($limit, 'homecare_signup'))(
            new HttpFactory(),
            $this->makeStorage(),
            new ClientIpResolver($params['app.trustedProxies']),
        );
    }

    public function oneIpIsStoppedAfterItsLimit(): void
    {
        $servers = array_fill(0, 12, ['REMOTE_ADDR' => '198.51.100.7']);

        // Characterisation: a configured limit of 5 lets only 4 through, because the library's
        // counter treats the last remaining slot as already limited (effective limit is N - 1).
        Assert::same($this->countAllowed($this->perIp(5), $servers), 4);
    }

    public function differentRealIpsEachGetTheirOwnBudget(): void
    {
        $servers = [];
        foreach (['198.51.100.1', '198.51.100.2'] as $ip) {
            for ($i = 0; $i < 5; $i++) {
                $servers[] = ['REMOTE_ADDR' => $ip];
            }
        }

        Assert::same($this->countAllowed($this->perIp(5), $servers), 8);
    }

    public function aSpoofedForwardedForHeaderDoesNotGiveAFreshBudget(): void
    {
        // The same real peer rotating X-Forwarded-For is still one bucket: the header is not
        // believed because the peer is not a trusted proxy.
        $servers = [];
        for ($i = 0; $i < 40; $i++) {
            $servers[] = ['REMOTE_ADDR' => '198.51.100.7', 'HTTP_X_FORWARDED_FOR' => '10.0.0.' . $i];
        }

        Assert::same($this->countAllowed($this->perIp(5), $servers), 4);
    }

    public function aSpoofedCloudflareHeaderDoesNotGiveAFreshBudget(): void
    {
        $servers = [];
        for ($i = 0; $i < 40; $i++) {
            $servers[] = ['REMOTE_ADDR' => '198.51.100.7', 'HTTP_CF_CONNECTING_IP' => '10.1.0.' . $i];
        }

        Assert::same($this->countAllowed($this->perIp(5), $servers), 4);
    }

    public function requestsViaCloudflareAreSeparatedByTheVisitorsOwnAddress(): void
    {
        // Every request arrives from a Cloudflare address; CF-Connecting-IP tells visitors apart.
        $servers = [];
        foreach (['203.0.113.1', '203.0.113.2'] as $visitor) {
            for ($i = 0; $i < 12; $i++) {
                $servers[] = ['REMOTE_ADDR' => '172.67.10.20', 'HTTP_CF_CONNECTING_IP' => $visitor];
            }
        }

        // each visitor gets their own budget of 4
        Assert::same($this->countAllowed($this->perIp(5), $servers), 8);
    }

    public function oneVisitorViaCloudflareIsStillStoppedAfterTheLimit(): void
    {
        $servers = array_fill(0, 12, ['REMOTE_ADDR' => '172.67.10.20', 'HTTP_CF_CONNECTING_IP' => '203.0.113.1']);

        Assert::same($this->countAllowed($this->perIp(5), $servers), 4);
    }

    /**
     * @param list<array<string, string>> $servers one GET per entry
     * @return list<int> the status codes in order
     */
    private function statusesForGets(MiddlewareInterface $middleware, array $servers): array
    {
        $statuses = [];
        foreach ($servers as $server) {
            $request = new HttpFactory()->createServerRequest('GET', 'https://example.test/en/homecare-signup/confirm/en/x/y', $server);
            $statuses[] = $middleware->process($request, $this->okHandler())->getStatusCode();
        }
        return $statuses;
    }

    private function perIpAnyMethod(int $limit): MiddlewareInterface
    {
        /** @var array{'app.trustedProxies': list<string>} $params */
        $params = require dirname(__DIR__, 3) . '/config/common/params.php';

        /** @var MiddlewareInterface */
        return (RateLimiter::perIpAnyMethod($limit, 'confirm'))(
            new HttpFactory(),
            $this->makeStorage(),
            new ClientIpResolver($params['app.trustedProxies']),
        );
    }

    public function theRedirectingPerIpLimiterNeverCountsGet(): void
    {
        // Why the confirm routes need their own variant: 40 GETs, none throttled.
        $statuses = $this->statusesForGets($this->perIp(5), array_fill(0, 40, ['REMOTE_ADDR' => '198.51.100.7']));

        Assert::same(array_unique($statuses), [Status::OK]);
    }

    public function theAnyMethodLimiterThrottlesGetWithAPlain429NotARedirect(): void
    {
        $statuses = $this->statusesForGets($this->perIpAnyMethod(5), array_fill(0, 12, ['REMOTE_ADDR' => '198.51.100.7']));

        Assert::same(array_count_values($statuses), [Status::OK => 4, Status::TOO_MANY_REQUESTS => 8]);
    }

    public function theAnyMethodLimiterKeepsVisitorsSeparateAndIgnoresSpoofedHeaders(): void
    {
        $servers = [];
        for ($i = 0; $i < 20; $i++) {
            $servers[] = ['REMOTE_ADDR' => '198.51.100.7', 'HTTP_X_FORWARDED_FOR' => '10.0.0.' . $i];
        }
        $servers[] = ['REMOTE_ADDR' => '198.51.100.8'];

        $statuses = $this->statusesForGets($this->perIpAnyMethod(5), $servers);

        Assert::same($statuses[20], Status::OK);
        Assert::same(count(array_filter(array_slice($statuses, 0, 20), static fn (int $s): bool => $s === Status::OK)), 4);
    }

    public function theAnyMethodGlobalLimiterCountsGetToo(): void
    {
        /** @var MiddlewareInterface $middleware */
        $middleware = (RateLimiter::globalAnyMethod(10))(new HttpFactory(), $this->makeStorage());
        $servers = [];
        for ($i = 0; $i < 30; $i++) {
            $servers[] = ['REMOTE_ADDR' => '198.51.100.' . $i];
        }

        $statuses = $this->statusesForGets($middleware, $servers);

        Assert::same(count(array_filter($statuses, static fn (int $s): bool => $s === Status::OK)), 9);
    }

    public function theGlobalLimiterCountsAllRequestersTogether(): void
    {
        $factory = new HttpFactory();
        /** @var MiddlewareInterface $middleware */
        $middleware = (RateLimiter::global(50, 10))($factory, $this->makeStorage());
        $servers = [];
        for ($i = 0; $i < 80; $i++) {
            $servers[] = ['REMOTE_ADDR' => '198.51.100.' . ($i % 250)];
        }

        Assert::same($this->countAllowed($middleware, $servers), 49);
    }
}
