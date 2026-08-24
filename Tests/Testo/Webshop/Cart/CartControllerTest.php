<?php

declare(strict_types=1);

namespace Tests\Testo\Webshop\Cart;

use App\Service\WebControllerService;
use App\Webshop\Cart\CartController;
use App\Webshop\Cart\CartService;
use App\Webshop\Catalog\CatalogQueryService;
use App\Webshop\Currency\CurrencyContext;
use App\Webshop\Currency\CurrencyInfo;
use App\Webshop\Currency\CurrencyInfoProvider;
use App\Webshop\Currency\CurrencyPreferenceService;
use App\Webshop\StorefrontViewParameters;
use Mockery as m;
use Nyholm\Psr7\Factory\Psr17Factory;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Testo\Assert;
use Testo\Test;
use Yiisoft\DataResponse\Formatter\JsonFormatter;
use Yiisoft\DataResponse\ResponseFactory\JsonResponseFactory;
use Yiisoft\Router\UrlGeneratorInterface;
use Yiisoft\Session\SessionInterface;
use Yiisoft\Yii\View\Renderer\WebViewRenderer;

/**
 * Covers CartController's JSON branch — the fetch()-based progressive
 * enhancement path `cart.ts` uses, picked purely from the request's
 * `Accept` header (see CartController::wantsJson()). The plain-form/
 * redirect branch this app already had is only re-checked here for the
 * "no json Accept header" case; it isn't otherwise duplicated, since it
 * hasn't changed since the standalone webshop app this was merged from
 * (see docs/WEBSHOP_INPROCESS_MERGE_AUGUST_2026.md).
 */
#[Test]
final class CartControllerTest
{
    private function fakeSession(): SessionInterface
    {
        /** @var array<string, mixed> $store */
        $store = [];
        /** @var SessionInterface&m\MockInterface $session */
        $session = m::mock(SessionInterface::class);
        $session->shouldReceive('get')->andReturnUsing(
            static function (string $key, mixed $default = null) use (&$store): mixed {
                return $store[$key] ?? $default;
            },
        );
        $session->shouldReceive('set')->andReturnUsing(
            /** @param array<int, array{name: string, price: float, quantity: float}> $value */
            static function (string $key, array $value) use (&$store): void {
                $store[$key] = $value;
            },
        );
        $session->shouldReceive('remove')->andReturnUsing(
            static function (string $key) use (&$store): void {
                unset($store[$key]);
            },
        );
        return $session;
    }

    private function currencyContext(?CurrencyInfo $info, string $preference): CurrencyContext
    {
        /** @var CurrencyInfoProvider&m\MockInterface $provider */
        $provider = m::mock(CurrencyInfoProvider::class);
        $provider->shouldReceive('get')->andReturn($info);

        /** @var CurrencyPreferenceService&m\MockInterface $preferenceService */
        $preferenceService = m::mock(CurrencyPreferenceService::class);
        $preferenceService->shouldReceive('get')->andReturn($preference);

        return new CurrencyContext($provider, $preferenceService);
    }

    private function controller(
        CartService $cartService,
        ?UrlGeneratorInterface $urlGenerator = null,
        ?CurrencyContext $currency = null,
    ): CartController {
        /** @var WebViewRenderer&m\MockInterface $renderer */
        $renderer = m::mock(WebViewRenderer::class);
        $renderer->shouldReceive('withControllerName')->andReturnSelf();
        $renderer->shouldReceive('withLayout')->andReturnSelf();

        if ($urlGenerator === null) {
            /** @var UrlGeneratorInterface&m\MockInterface $urlGenerator */
            $urlGenerator = m::mock(UrlGeneratorInterface::class);
        }

        $psr17 = new Psr17Factory();

        /** @var StorefrontViewParameters&m\MockInterface $chrome */
        $chrome = m::mock(StorefrontViewParameters::class);

        // Never actually called by the update()/remove() actions this
        // test class covers (see the class docblock) — only index()/
        // add() touch the catalog, and neither is exercised here.
        /** @var CatalogQueryService&m\MockInterface $catalog */
        $catalog = m::mock(CatalogQueryService::class);

        return new CartController(
            $renderer,
            new WebControllerService($psr17, $psr17, $urlGenerator),
            $cartService,
            new JsonResponseFactory($psr17, new JsonFormatter()),
            // No currency pair configured by default —
            // CurrencyContext::format() then behaves as a bare
            // number_format(), same as before this dependency existed,
            // so existing assertions below still hold.
            $currency ?? $this->currencyContext(null, CurrencyPreferenceService::NATIVE),
            $chrome,
            $catalog,
        );
    }

    private function jsonRequest(): ServerRequestInterface
    {
        return new Psr17Factory()
            ->createServerRequest('POST', '/shop/cart/update')
            ->withHeader('Accept', 'application/json');
    }

    /** @return array<string, mixed> */
    private function decode(ResponseInterface $response): array
    {
        /** @var array<string, mixed> */
        return json_decode((string) $response->getBody(), true);
    }

    public function updateReturnsTheNewSubtotalAndTotalAsJson(): void
    {
        $cartService = new CartService($this->fakeSession());
        $cartService->add(1, 'Widget', 9.99, 2.0);

        $request = $this->jsonRequest()->withParsedBody(['product_id' => '1', 'quantity' => '4']);
        $response = $this->controller($cartService)->update($request);
        $data = $this->decode($response);

        Assert::same('application/json; charset=UTF-8', $response->getHeaderLine('Content-Type'));
        Assert::same(1, $data['productId']);
        // json_decode() returns an int for a whole-number JSON literal
        // (no int/float distinction in JSON itself) — cast back to match
        // what CartItem::$quantity actually is.
        Assert::same(4.0, (float) $data['quantity']);
        Assert::same(39.96, $data['subtotal']);
        Assert::same('39.96', $data['subtotalFormatted']);
        Assert::false($data['removed']);
        Assert::same(1, $data['count']);
    }

    public function updateToZeroReportsTheItemAsRemoved(): void
    {
        $cartService = new CartService($this->fakeSession());
        $cartService->add(1, 'Widget', 9.99, 2.0);

        $request = $this->jsonRequest()->withParsedBody(['product_id' => '1', 'quantity' => '0']);
        $response = $this->controller($cartService)->update($request);
        $data = $this->decode($response);

        Assert::true($data['removed']);
        Assert::null($data['quantity']);
        Assert::null($data['subtotalFormatted']);
        Assert::same(0, $data['count']);
    }

    public function subtotalAndTotalFormattedGoThroughTheInjectedCurrencyContext(): void
    {
        $cartService = new CartService($this->fakeSession());
        $cartService->add(1, 'Widget', 10.00, 2.0);

        $currency = $this->currencyContext(
            new CurrencyInfo(native: 'GBP', document: 'EUR', nativeToDocumentRate: 1.5, documentToNativeRate: 0.67),
            CurrencyPreferenceService::DOCUMENT,
        );

        $request = $this->jsonRequest()->withParsedBody(['product_id' => '1', 'quantity' => '2']);
        $response = $this->controller($cartService, currency: $currency)->update($request);
        $data = $this->decode($response);

        // Raw subtotal/total stay the real native-currency amount —
        // only the *Formatted fields reflect the display conversion.
        // (json_decode() returns an int for a whole-number JSON literal.)
        Assert::same(20.0, (float) $data['subtotal']);
        Assert::same('€30.00', $data['subtotalFormatted']);
        Assert::same('€30.00', $data['totalFormatted']);
    }

    public function removeReturnsTheUpdatedTotals(): void
    {
        $cartService = new CartService($this->fakeSession());
        $cartService->add(1, 'Widget', 9.99, 1.0);
        $cartService->add(2, 'Gadget', 4.5, 1.0);

        $response = $this->controller($cartService)->remove($this->jsonRequest(), 1);
        $data = $this->decode($response);

        Assert::true($data['removed']);
        Assert::same(1, $data['count']);
        Assert::same(4.5, $data['total']);
        Assert::same('4.50', $data['totalFormatted']);
    }

    public function updateWithoutAJsonAcceptHeaderStillRedirects(): void
    {
        $cartService = new CartService($this->fakeSession());
        $cartService->add(1, 'Widget', 9.99, 2.0);

        /** @var UrlGeneratorInterface&m\MockInterface $urlGenerator */
        $urlGenerator = m::mock(UrlGeneratorInterface::class);
        $urlGenerator->shouldReceive('generate')->with('shop/cart/index', [], [], null)->andReturn('/shop/cart');

        $request = new Psr17Factory()
            ->createServerRequest('POST', '/shop/cart/update')
            ->withParsedBody(['product_id' => '1', 'quantity' => '4']);

        $response = $this->controller($cartService, $urlGenerator)->update($request);

        Assert::same(302, $response->getStatusCode());
        Assert::same('/shop/cart', $response->getHeaderLine('Location'));
    }
}
