<?php

declare(strict_types=1);

namespace Tests\PHPUnit\Middleware;

use App\Middleware\PageOutOfRangeMiddleware;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;
use Psr\Http\Server\RequestHandlerInterface;
use RuntimeException;
use Yiisoft\Data\Paginator\PageNotFoundException;

#[AllowMockObjectsWithoutExpectations]
final class PageOutOfRangeMiddlewareTest extends TestCase
{
    private ResponseFactoryInterface $responseFactory;
    private PageOutOfRangeMiddleware $middleware;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->responseFactory = $this->createMock(ResponseFactoryInterface::class);
        $this->middleware = new PageOutOfRangeMiddleware($this->responseFactory);
    }

    public function testPassesThroughResponseWhenNoException(): void
    {
        $response = $this->createMock(ResponseInterface::class);
        $request  = $this->createMock(ServerRequestInterface::class);
        $handler  = $this->createMock(RequestHandlerInterface::class);

        $handler->expects($this->once())
            ->method('handle')
            ->with($request)
            ->willReturn($response);

        $this->assertSame($response, $this->middleware->process($request, $handler));
    }

    public function testRedirectsToPageOneOnPageNotFoundException(): void
    {
        $newUri        = $this->createMock(UriInterface::class);
        $finalResponse = $this->createMock(ResponseInterface::class);

        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->method('handle')->willThrowException(new PageNotFoundException(4));

        $uri = $this->createMock(UriInterface::class);
        $uri->expects($this->once())
            ->method('withQuery')
            ->with(http_build_query(['page' => '1']))
            ->willReturn($newUri);

        $newUri->method('__toString')->willReturn('https://example.com/inv?page=1');

        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getQueryParams')->willReturn(['page' => '4']);
        $request->method('getUri')->willReturn($uri);

        $rawResponse = $this->createMock(ResponseInterface::class);
        $rawResponse->expects($this->once())
            ->method('withHeader')
            ->with('Location', 'https://example.com/inv?page=1')
            ->willReturn($finalResponse);

        $responseFactory = $this->createMock(ResponseFactoryInterface::class);
        $responseFactory->expects($this->once())
            ->method('createResponse')
            ->with(302)
            ->willReturn($rawResponse);

        $middleware = new PageOutOfRangeMiddleware($responseFactory);

        $this->assertSame($finalResponse, $middleware->process($request, $handler));
    }

    public function testPreservesExistingQueryParamsAndResetsPage(): void
    {
        $newUri        = $this->createMock(UriInterface::class);
        $rawResponse   = $this->createMock(ResponseInterface::class);
        $finalResponse = $this->createMock(ResponseInterface::class);

        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->method('handle')->willThrowException(new PageNotFoundException(5));

        $uri = $this->createMock(UriInterface::class);
        $uri->expects($this->once())
            ->method('withQuery')
            ->with(http_build_query(['page' => '1', 'sort' => '-id', 'filterStatus' => '2']))
            ->willReturn($newUri);

        $newUri->method('__toString')->willReturn('https://example.com/inv?page=1&sort=-id&filterStatus=2');

        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getQueryParams')
            ->willReturn(['page' => '5', 'sort' => '-id', 'filterStatus' => '2']);
        $request->method('getUri')->willReturn($uri);

        $rawResponse->method('withHeader')->willReturn($finalResponse);
        $this->responseFactory->method('createResponse')->willReturn($rawResponse);

        $this->assertSame($finalResponse, $this->middleware->process($request, $handler));
    }

    public function testAddsPageParamWhenQueryStringIsEmpty(): void
    {
        $newUri        = $this->createMock(UriInterface::class);
        $rawResponse   = $this->createMock(ResponseInterface::class);
        $finalResponse = $this->createMock(ResponseInterface::class);

        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->method('handle')->willThrowException(new PageNotFoundException(1));

        $uri = $this->createMock(UriInterface::class);
        $uri->expects($this->once())
            ->method('withQuery')
            ->with(http_build_query(['page' => '1']))
            ->willReturn($newUri);

        $newUri->method('__toString')->willReturn('https://example.com/inv?page=1');

        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getQueryParams')->willReturn([]);
        $request->method('getUri')->willReturn($uri);

        $rawResponse->method('withHeader')->willReturn($finalResponse);
        $this->responseFactory->method('createResponse')->willReturn($rawResponse);

        $this->assertSame($finalResponse, $this->middleware->process($request, $handler));
    }

    public function testNonPageNotFoundExceptionBubblesUp(): void
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->method('handle')->willThrowException(new RuntimeException('Unrelated error'));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Unrelated error');

        $this->middleware->process($request, $handler);
    }
}
