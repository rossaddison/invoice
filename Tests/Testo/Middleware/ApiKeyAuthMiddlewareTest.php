<?php

declare(strict_types=1);

namespace Tests\Testo\Middleware;

use App\Api\ApiClientRepository;
use App\Infrastructure\Persistence\ApiClient\ApiClient;
use App\Middleware\ApiKeyAuthMiddleware;
use GuzzleHttp\Psr7\HttpFactory;
use Mockery as m;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;
use Testo\Assert;
use Testo\Test;
use Yiisoft\DataResponse\ResponseFactory\DataResponseFactoryInterface;

/**
 * Covers ApiKeyAuthMiddleware — the `X-Api-Key` gate for external-partner
 * `/api` routes (see docs/WEBSHOP_HEADLESS_STOREFRONT_DESIGN_AUGUST_2026.md).
 * ApiClientRepository is `final`, mockable here only because
 * Tests/bootstrap.php enables DG\BypassFinals — same established pattern as
 * InvItemServiceTest.
 */
#[Test]
final class ApiKeyAuthMiddlewareTest
{
    /** Throws if called — proves rejection short-circuited before the real handler. */
    private function makeThrowingHandler(): RequestHandlerInterface
    {
        return new class implements RequestHandlerInterface {
            #[\Override]
            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                throw new \LogicException('handler should not be called in this test');
            }
        };
    }

    private function makeMiddleware(ApiClientRepository $repository): ApiKeyAuthMiddleware
    {
        /** @var DataResponseFactoryInterface&m\MockInterface $responseFactory */
        $responseFactory = m::mock(DataResponseFactoryInterface::class);
        $responseFactory->shouldReceive('createResponse')
            ->andReturnUsing(static fn (mixed $data, int $code = 200) => new HttpFactory()->createResponse($code));

        /** @var LoggerInterface $logger */
        $logger = m::spy(LoggerInterface::class);

        return new ApiKeyAuthMiddleware($repository, $responseFactory, $logger);
    }

    public function rejectsARequestWithNoApiKeyHeaderAtAll(): void
    {
        /** @var ApiClientRepository&m\MockInterface $repository */
        $repository = m::mock(ApiClientRepository::class);
        $repository->shouldNotReceive('findActiveByKeyHash');
        $middleware = $this->makeMiddleware($repository);

        $request = new HttpFactory()->createServerRequest('GET', 'https://example.test/api/products');

        $result = $middleware->process($request, $this->makeThrowingHandler());

        Assert::same(401, $result->getStatusCode());
    }

    public function rejectsARequestWithAnUnknownOrDisabledKey(): void
    {
        /** @var ApiClientRepository&m\MockInterface $repository */
        $repository = m::mock(ApiClientRepository::class);
        $repository->shouldReceive('findActiveByKeyHash')
            ->with(hash('sha256', 'wrong-key'))
            ->andReturn(null);
        $middleware = $this->makeMiddleware($repository);

        $request = new HttpFactory()->createServerRequest('GET', 'https://example.test/api/products')
            ->withHeader('X-Api-Key', 'wrong-key');

        $result = $middleware->process($request, $this->makeThrowingHandler());

        Assert::same(401, $result->getStatusCode());
    }

    public function passesThroughAndAttachesTheClientForAValidKey(): void
    {
        $client = new ApiClient(name: 'webshop', key_hash: hash('sha256', 'real-key'));
        $client->setId(1);

        /** @var ApiClientRepository&m\MockInterface $repository */
        $repository = m::mock(ApiClientRepository::class);
        $repository->shouldReceive('findActiveByKeyHash')
            ->with(hash('sha256', 'real-key'))
            ->andReturn($client);
        $repository->shouldReceive('save')->once()->with($client);
        $middleware = $this->makeMiddleware($repository);

        $request = new HttpFactory()->createServerRequest('GET', 'https://example.test/api/products')
            ->withHeader('X-Api-Key', 'real-key');
        $handler = new class implements RequestHandlerInterface {
            public ?ServerRequestInterface $received = null;

            #[\Override]
            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                $this->received = $request;
                return new HttpFactory()->createResponse(200);
            }
        };

        $result = $middleware->process($request, $handler);

        Assert::same(200, $result->getStatusCode());
        Assert::notNull($handler->received);
        Assert::same($client, $handler->received->getAttribute('apiClient'));
        Assert::notNull($client->getLastUsedAt());
    }
}
