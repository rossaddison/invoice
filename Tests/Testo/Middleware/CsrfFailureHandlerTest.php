<?php

declare(strict_types=1);

namespace Tests\Testo\Middleware;

use App\Middleware\CsrfFailureHandler;
use App\Service\WebControllerService;
use Mockery as m;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Testo\Assert;
use Testo\Test;

/**
 * @see CsrfFailureHandler
 *
 * Wired directly as Yiisoft\Csrf\CsrfTokenMiddleware's own $failureHandler
 * (config/common/di/router.php) -- the library itself only ever invokes
 * this at CSRF's genuine point of failure, and never for safe methods
 * (GET/HEAD/OPTIONS), so unlike a response-status-sniffing middleware
 * there is no method/status-code branching here to test: handle() always
 * does exactly one thing.
 */
#[Test]
final class CsrfFailureHandlerTest
{
    public function handleRedirectsToSiteIndex(): void
    {
        /** @var Request&m\MockInterface $request */
        $request = m::mock(Request::class);

        /** @var Response&m\MockInterface $redirect */
        $redirect = m::mock(Response::class);
        /** @var WebControllerService&m\MockInterface $webService */
        $webService = m::mock(WebControllerService::class);
        $webService->shouldReceive('getRedirectResponse')->once()->with('site/index')->andReturn($redirect);

        $handler = new CsrfFailureHandler($webService);

        Assert::same($handler->handle($request), $redirect);
    }
}
