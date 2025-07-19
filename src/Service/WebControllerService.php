<?php

declare(strict_types=1);

namespace App\Service;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Yiisoft\Http\Header;
use Yiisoft\Http\Status;
use Yiisoft\Router\UrlGeneratorInterface;

final readonly class WebControllerService
{
    public function __construct(
        private ResponseFactoryInterface $responseFactory,
        private UrlGeneratorInterface $urlGenerator,
    ) {}

    /** Bug: Trailing # at end of browser url if ... string $hash = '';
     *  Fix: ?string $hash = null
     *  @see
     */
    /** @psalm-suppress MixedArgumentTypeCoercion $arguments **/
    public function getRedirectResponse(string $url, array $arguments = [], array $queryParameters = [], ?string $hash = null): ResponseInterface
    {
        return $this->responseFactory
            ->createResponse(Status::FOUND)
            ->withHeader(Header::LOCATION, $this->urlGenerator->generate($url, $arguments, $queryParameters, $hash));
    }

    public function getNotFoundResponse(): ResponseInterface
    {
        return $this->responseFactory
            ->createResponse(Status::NOT_FOUND);
    }
}
