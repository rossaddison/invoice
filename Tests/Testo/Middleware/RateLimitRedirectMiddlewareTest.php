<?php

declare(strict_types=1);

namespace Tests\Testo\Middleware;

use App\Middleware\RateLimitRedirectMiddleware;
use GuzzleHttp\Psr7\HttpFactory;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Testo\Assert;
use Testo\Test;
use Yiisoft\Http\Header;
use Yiisoft\Http\Status;

/**
 * @see RateLimitRedirectMiddleware
 */
#[Test]
final class RateLimitRedirectMiddlewareTest
{
    /** Always returns $statusCode — used to stand in for the wrapped limiter. */
    private function makeInner(int $statusCode): MiddlewareInterface
    {
        return new class ($statusCode) implements MiddlewareInterface {
            public function __construct(private readonly int $statusCode)
            {
            }

            #[\Override]
            public function process(
                ServerRequestInterface $request,
                RequestHandlerInterface $handler,
            ): ResponseInterface {
                return new HttpFactory()->createResponse($this->statusCode);
            }
        };
    }

    /** Throws if called — proves the caller reached the real handler, not the inner limiter's stub. */
    private function makeThrowingHandler(): RequestHandlerInterface
    {
        return new class () implements RequestHandlerInterface {
            #[\Override]
            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                throw new \LogicException('handler should not be called in this test');
            }
        };
    }

    /** Returns a fixed 200 with a recognisable body, to prove it — not the inner limiter — ran. */
    private function makeOkHandler(): RequestHandlerInterface
    {
        return new class () implements RequestHandlerInterface {
            #[\Override]
            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                $response = new HttpFactory()->createResponse(Status::OK);
                $response->getBody()->write('handled-directly');
                return $response;
            }
        };
    }

    public function redirectsToTheSameUriWhenAPostRequestHitsTheLimit(): void
    {
        $factory = new HttpFactory();
        $middleware = new RateLimitRedirectMiddleware($this->makeInner(Status::TOO_MANY_REQUESTS), $factory);
        $request = $factory->createServerRequest('POST', 'https://example.test/en/login');

        $result = $middleware->process($request, $this->makeThrowingHandler());

        Assert::same($result->getStatusCode(), Status::FOUND);
        Assert::same($result->getHeaderLine(Header::LOCATION), 'https://example.test/en/login');
    }

    public function passesThroughUnchangedWhenAPostRequestDoesNotHitTheLimit(): void
    {
        $factory = new HttpFactory();
        $middleware = new RateLimitRedirectMiddleware($this->makeInner(Status::OK), $factory);
        $request = $factory->createServerRequest('POST', 'https://example.test/en/login');

        $result = $middleware->process($request, $this->makeThrowingHandler());

        Assert::same($result->getStatusCode(), Status::OK);
        Assert::same($result->getHeaderLine(Header::LOCATION), '');
    }

    public function passesThroughOtherErrorStatusesUnchangedOnPost(): void
    {
        // Only 429 is special-cased — anything else (a 500 from downstream,
        // say) must not be silently swallowed into a redirect.
        $factory = new HttpFactory();
        $middleware = new RateLimitRedirectMiddleware(
            $this->makeInner(Status::INTERNAL_SERVER_ERROR),
            $factory,
        );
        $request = $factory->createServerRequest('POST', 'https://example.test/en/login');

        $result = $middleware->process($request, $this->makeThrowingHandler());

        Assert::same($result->getStatusCode(), Status::INTERNAL_SERVER_ERROR);
    }

    /**
     * The regression this test guards against was found live, not in a
     * unit test: a real browser hit ERR_TOO_MANY_REDIRECTS because a GET
     * page view was itself being counted against the same limiter as the
     * POST submissions, and a GET that trips the limit was redirecting
     * back to that exact same (still over-budget) GET, looping forever
     * for the rest of the window. GET must never reach the inner limiter
     * at all — the inner stub here returns 429 unconditionally, so this
     * test would fail immediately (a 302-to-self loop, exactly like the
     * live bug) if that guard regressed.
     */
    public function neverConsultsTheInnerLimiterForASafeMethod(): void
    {
        $factory = new HttpFactory();
        $middleware = new RateLimitRedirectMiddleware($this->makeInner(Status::TOO_MANY_REQUESTS), $factory);
        $request = $factory->createServerRequest('GET', 'https://example.test/en/login');

        $result = $middleware->process($request, $this->makeOkHandler());

        Assert::same($result->getStatusCode(), Status::OK);
        Assert::same((string) $result->getBody(), 'handled-directly');
    }

    public function neverConsultsTheInnerLimiterForHeadOrOptions(): void
    {
        $factory = new HttpFactory();

        foreach (['HEAD', 'OPTIONS'] as $method) {
            $middleware = new RateLimitRedirectMiddleware($this->makeInner(Status::TOO_MANY_REQUESTS), $factory);
            $request = $factory->createServerRequest($method, 'https://example.test/en/login');

            $result = $middleware->process($request, $this->makeOkHandler());

            Assert::same($result->getStatusCode(), Status::OK);
        }
    }
}
