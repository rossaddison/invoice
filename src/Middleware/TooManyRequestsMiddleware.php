<?php

declare(strict_types=1);

namespace App\Middleware;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Yiisoft\Http\Status;

/**
 * Returns 429 Too Many Requests when the rate-limiter counter fails to
 * persist its CAS value under high concurrency (FileCache contention).
 * Passed as $failStoreUpdatedDataMiddleware to LimitRequestsMiddleware so
 * that CAS failures deny the request rather than silently allowing it.
 */
final class TooManyRequestsMiddleware implements MiddlewareInterface
{
    public function __construct(private readonly ResponseFactoryInterface $responseFactory)
    {
    }

    #[\Override]
    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler,
    ): ResponseInterface {
        $response = $this->responseFactory->createResponse(Status::TOO_MANY_REQUESTS);
        $response->getBody()->write(Status::TEXTS[Status::TOO_MANY_REQUESTS]);
        return $response;
    }
}
