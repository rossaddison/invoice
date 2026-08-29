<?php

declare(strict_types=1);

namespace App\Service;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Yiisoft\Http\Header;
use Yiisoft\Http\Status;
use Yiisoft\Router\UrlGeneratorInterface;

final readonly class WebControllerService
{
    public function __construct(
        private ResponseFactoryInterface $responseFactory,
        private StreamFactoryInterface   $streamFactory,
        private UrlGeneratorInterface $urlGenerator,
    ) {
    }

    /** Bug: Trailing # at end of browser url if ... string $hash = '';
     *  Fix: ?string $hash = null
     *  Related logic: see
     */
    /** @psalm-suppress MixedArgumentTypeCoercion $arguments **/
    public function getRedirectResponse(string $url, array $arguments = [], array $queryParameters = [], ?string $hash = null): ResponseInterface
    {
        return $this->responseFactory
            ->createResponse(Status::FOUND)
            ->withHeader(Header::LOCATION, $this->urlGenerator->generate($url, $arguments, $queryParameters, $hash));
    }

    /**
     * Same route/arguments/hash as `getRedirectResponse()`, but returns the
     * URL string itself instead of a redirect — for embedding a deep link
     * (e.g. straight to the specific field an error is about, via `$hash`
     * matching that field's HTML id) inside a flash message body rather
     * than navigating there immediately.
     */
    /** @psalm-suppress MixedArgumentTypeCoercion $arguments **/
    public function generateUrl(string $url, array $arguments = [], array $queryParameters = [], ?string $hash = null): string
    {
        return $this->urlGenerator->generate($url, $arguments, $queryParameters, $hash);
    }

    /**
     * Redirects to a raw path rather than a named route — for the rare
     * case (`App\Webshop\Delivery\DeliveryController::update()`,
     * `App\Webshop\Currency\CurrencyController::update()`) where the
     * target is "wherever the visitor actually was", not a route this
     * app can name ahead of time.
     *
     * @param string $path Must already be a same-origin, root-relative
     *     path (leading single `/`, no `://`) — callers building it from
     *     request input are responsible for that, since a raw
     *     unvalidated value here would be an open redirect. Prefer
     *     `getRedirectToSameOriginPathResponse()` unless that validation
     *     has already happened.
     */
    public function getRedirectToPathResponse(string $path): ResponseInterface
    {
        return $this->responseFactory
            ->createResponse(Status::FOUND)
            ->withHeader(Header::LOCATION, $path);
    }

    /**
     * Same as `getRedirectToPathResponse()`, but validates `$path` itself
     * first — for a hidden form field a controller filled in server-side
     * with the current page's own URL (so submitting lands back where
     * the visitor was), which is nonetheless still request input a
     * crafted POST could replace with `//evil.example` or
     * `https://evil.example` (an open redirect). Falls back to `/`
     * unless `$path` is still a root-relative, same-origin path.
     */
    public function getRedirectToSameOriginPathResponse(string $path): ResponseInterface
    {
        return $this->getRedirectToPathResponse($this->safeSameOriginPath($path));
    }

    private function safeSameOriginPath(string $path): string
    {
        if ($path === '' || $path[0] !== '/' || str_starts_with($path, '//') || str_contains($path, '://')) {
            return '/';
        }
        return $path;
    }

    public function getNotFoundResponse(): ResponseInterface
    {
        return $this->responseFactory
            ->createResponse(Status::NOT_FOUND);
    }

    public function getHtmlResponse(string $html): ResponseInterface
    {
        return $this->responseFactory
            ->createResponse(Status::OK)
            ->withHeader(Header::CONTENT_TYPE, 'text/html; charset=UTF-8')
            ->withBody($this->streamFactory->createStream($html));
    }
}
