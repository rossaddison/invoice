<?php

declare(strict_types=1);

namespace App\Middleware;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Yiisoft\Data\Paginator\PageNotFoundException;

/**
 * Catches PageNotFoundException thrown by any OffsetPaginator and redirects
 * to the same URL with page=1, preventing stale pagination URLs from
 * causing 500 errors after records are deleted or filtered.
 */
final class PageOutOfRangeMiddleware implements MiddlewareInterface
{
    public function __construct(
        private readonly ResponseFactoryInterface $responseFactory,
    ) {}

    #[\Override]
    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler,
    ): ResponseInterface {
        try {
            return $handler->handle($request);
        } catch (PageNotFoundException) {
            $params = $request->getQueryParams();
            $params['page'] = '1';
            $newUri = $request->getUri()->withQuery(http_build_query($params));

            return $this->responseFactory
                ->createResponse(302)
                ->withHeader('Location', (string) $newUri);
        }
    }
}
