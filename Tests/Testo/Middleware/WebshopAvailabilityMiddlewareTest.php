<?php

declare(strict_types=1);

namespace Tests\Testo\Middleware;

use App\Invoice\Setting\SettingRepository;
use App\Middleware\WebshopAvailabilityMiddleware;
use App\Service\WebControllerService;
use Mockery as m;
use Nyholm\Psr7\Factory\Psr17Factory;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Testo\Assert;
use Testo\Test;
use Yiisoft\Http\Status;
use Yiisoft\Router\UrlGeneratorInterface;

/**
 * Covers WebshopAvailabilityMiddleware -- the actual route-level "no
 * entry" gate for /shop, as opposed to LayoutViewInjection's
 * noFrontPageWebshop, which only ever hid the navbar link.
 *
 * @see WebshopAvailabilityMiddleware
 */
#[Test]
final class WebshopAvailabilityMiddlewareTest
{
    private const string SHOP_URI = 'https://example.test/shop';

    private function webService(): WebControllerService
    {
        $psr17 = new Psr17Factory();
        /** @var UrlGeneratorInterface&m\MockInterface $urlGenerator */
        $urlGenerator = m::mock(UrlGeneratorInterface::class);
        return new WebControllerService($psr17, $psr17, $urlGenerator);
    }

    private function settingRepository(string $noFrontWebshopPage): SettingRepository
    {
        /** @var SettingRepository&m\MockInterface $s */
        $s = m::mock(SettingRepository::class);
        $s->shouldReceive('getSetting')->with('no_front_webshop_page')->andReturn($noFrontWebshopPage);
        return $s;
    }

    /** Throws if called -- proves the request never reached the real route handler. */
    private function throwingHandler(): RequestHandlerInterface
    {
        return new class () implements RequestHandlerInterface {
            #[\Override]
            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                throw new \LogicException('handler should not be called when the webshop is switched off');
            }
        };
    }

    private function okHandler(): RequestHandlerInterface
    {
        return new class () implements RequestHandlerInterface {
            #[\Override]
            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                return new Psr17Factory()->createResponse(Status::OK);
            }
        };
    }

    public function returns404AndNeverReachesTheHandlerWhenTheSettingIsOn(): void
    {
        $middleware = new WebshopAvailabilityMiddleware($this->settingRepository('1'), $this->webService());
        $request = new Psr17Factory()->createServerRequest('GET', self::SHOP_URI);

        $result = $middleware->process($request, $this->throwingHandler());

        Assert::same($result->getStatusCode(), Status::NOT_FOUND);
    }

    public function passesThroughToTheHandlerWhenTheSettingIsOff(): void
    {
        $middleware = new WebshopAvailabilityMiddleware($this->settingRepository('0'), $this->webService());
        $request = new Psr17Factory()->createServerRequest('GET', self::SHOP_URI);

        $result = $middleware->process($request, $this->okHandler());

        Assert::same($result->getStatusCode(), Status::OK);
    }

    public function passesThroughToTheHandlerWhenTheSettingWasNeverSet(): void
    {
        // SettingRepository::getSetting() returns '' for a key that was
        // never persisted, never null -- see its own implementation.
        $middleware = new WebshopAvailabilityMiddleware($this->settingRepository(''), $this->webService());
        $request = new Psr17Factory()->createServerRequest('GET', self::SHOP_URI);

        $result = $middleware->process($request, $this->okHandler());

        Assert::same($result->getStatusCode(), Status::OK);
    }

    /**
     * Every /shop/* route is behind this same middleware instance (it's
     * wired onto the whole Group::create('/shop'), not per-route) -- this
     * just confirms the gate itself doesn't care which path it's given,
     * since that's what actually closes the hole: cart/checkout/etc. were
     * just as directly reachable by URL as the catalog index was.
     */
    public function gatesAnyShopPathNotJustTheCatalogIndex(): void
    {
        $middleware = new WebshopAvailabilityMiddleware($this->settingRepository('1'), $this->webService());
        $request = new Psr17Factory()->createServerRequest('POST', 'https://example.test/shop/checkout');

        $result = $middleware->process($request, $this->throwingHandler());

        Assert::same($result->getStatusCode(), Status::NOT_FOUND);
    }
}
