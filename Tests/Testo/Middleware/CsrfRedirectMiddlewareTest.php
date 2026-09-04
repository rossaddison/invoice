<?php

declare(strict_types=1);

namespace Tests\Testo\Middleware;

use App\Middleware\CsrfExemptMiddleware;
use App\Middleware\CsrfRedirectMiddleware;
use App\Service\WebControllerService;
use GuzzleHttp\Psr7\HttpFactory;
use Mockery as m;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface;
use Testo\Assert;
use Testo\Test;
use Yiisoft\Http\Status;

/**
 * @see CsrfRedirectMiddleware
 *
 * Real symptom this guards against: clicking Logout after a page sat open
 * long enough for the session (and the CSRF token embedded in it) to go
 * stale landed on CsrfTokenMiddleware's own raw, unstyled 422 -- see
 * CsrfRedirectMiddleware's own docblock and
 * project_csrf_redirect_middleware memory for the full diagnosis.
 */
#[Test]
final class CsrfRedirectMiddlewareTest
{
    private function makeThrowingHandler(): RequestHandlerInterface
    {
        return new class implements RequestHandlerInterface {
            #[\Override]
            public function handle(Request $request): Response
            {
                throw new \LogicException('handler should not be called in this test');
            }
        };
    }

    /** Returns a fixed 200 with a recognisable body, to prove it — not the inner middleware — ran. */
    private function makeOkHandler(): RequestHandlerInterface
    {
        return new class implements RequestHandlerInterface {
            #[\Override]
            public function handle(Request $request): Response
            {
                $response = new HttpFactory()->createResponse(Status::OK);
                $response->getBody()->write('handled-directly');
                return $response;
            }
        };
    }

    /**
     * @return CsrfExemptMiddleware&m\MockInterface
     */
    private function makeInner(int $statusCode): CsrfExemptMiddleware
    {
        /** @var CsrfExemptMiddleware&m\MockInterface $inner */
        $inner = m::mock(CsrfExemptMiddleware::class);
        $inner->shouldReceive('process')->once()->andReturn(new HttpFactory()->createResponse($statusCode));
        return $inner;
    }

    /**
     * @return WebControllerService&m\MockInterface
     */
    private function makeUnusedWebService(): WebControllerService
    {
        /** @var WebControllerService&m\MockInterface $webService */
        $webService = m::mock(WebControllerService::class);
        $webService->shouldNotReceive('getRedirectResponse');
        return $webService;
    }

    public function redirectsToSiteIndexWhenCsrfValidationFails(): void
    {
        $request = new HttpFactory()->createServerRequest('POST', 'https://example.test/en/logout');

        /** @var Response&m\MockInterface $redirect */
        $redirect = m::mock(Response::class);
        /** @var WebControllerService&m\MockInterface $webService */
        $webService = m::mock(WebControllerService::class);
        $webService->shouldReceive('getRedirectResponse')->once()->with('site/index')->andReturn($redirect);

        $middleware = new CsrfRedirectMiddleware($this->makeInner(Status::UNPROCESSABLE_ENTITY), $webService);

        $result = $middleware->process($request, $this->makeThrowingHandler());

        Assert::same($result, $redirect);
    }

    public function passesThroughUnchangedWhenCsrfValidationSucceeds(): void
    {
        $request = new HttpFactory()->createServerRequest('POST', 'https://example.test/en/logout');

        $middleware = new CsrfRedirectMiddleware($this->makeInner(Status::FOUND), $this->makeUnusedWebService());

        $result = $middleware->process($request, $this->makeThrowingHandler());

        Assert::same($result->getStatusCode(), Status::FOUND);
    }

    public function passesThroughOtherErrorStatusesUnchanged(): void
    {
        // Only 422 is special-cased -- anything else (a 500 from
        // downstream, say) must not be silently swallowed into a redirect.
        $request = new HttpFactory()->createServerRequest('POST', 'https://example.test/en/logout');

        $middleware = new CsrfRedirectMiddleware(
            $this->makeInner(Status::INTERNAL_SERVER_ERROR),
            $this->makeUnusedWebService(),
        );

        $result = $middleware->process($request, $this->makeThrowingHandler());

        Assert::same($result->getStatusCode(), Status::INTERNAL_SERVER_ERROR);
    }

    public function neverConsultsTheInnerMiddlewareForASafeMethod(): void
    {
        $request = new HttpFactory()->createServerRequest('GET', 'https://example.test/en/logout');

        /** @var CsrfExemptMiddleware&m\MockInterface $inner */
        $inner = m::mock(CsrfExemptMiddleware::class);
        $inner->shouldNotReceive('process');

        $middleware = new CsrfRedirectMiddleware($inner, $this->makeUnusedWebService());

        $result = $middleware->process($request, $this->makeOkHandler());

        Assert::same($result->getStatusCode(), Status::OK);
        Assert::same((string) $result->getBody(), 'handled-directly');
    }

    public function neverConsultsTheInnerMiddlewareForHeadOrOptions(): void
    {
        foreach (['HEAD', 'OPTIONS'] as $method) {
            $request = new HttpFactory()->createServerRequest($method, 'https://example.test/en/logout');

            /** @var CsrfExemptMiddleware&m\MockInterface $inner */
            $inner = m::mock(CsrfExemptMiddleware::class);
            $inner->shouldNotReceive('process');

            $middleware = new CsrfRedirectMiddleware($inner, $this->makeUnusedWebService());

            $result = $middleware->process($request, $this->makeOkHandler());

            Assert::same($result->getStatusCode(), Status::OK);
        }
    }
}
