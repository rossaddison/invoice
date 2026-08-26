<?php

declare(strict_types=1);

namespace Tests\Testo\Webshop\Cart;

use App\Service\WebControllerService;
use App\Webshop\Cart\CartController;
use App\Webshop\Cart\CartService;
use App\Webshop\Catalog\CatalogQueryService;
use App\Webshop\Catalog\ProductListing;
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
use Yiisoft\Session\Flash\Flash;
use Yiisoft\Session\SessionInterface;
use Yiisoft\Translator\TranslatorInterface;
use Yiisoft\Yii\View\Renderer\WebViewRenderer;

/**
 * Covers CartController's JSON branch — the fetch()-based progressive
 * enhancement path `cart.ts` uses, picked purely from the request's
 * `Accept` header (see CartController::wantsJson()). The plain-form/
 * redirect branch this app already had is only re-checked here for the
 * "no json Accept header" case; it isn't otherwise duplicated, since it
 * hasn't changed since the standalone webshop app this was merged from
 * (see docs/WEBSHOP_INPROCESS_MERGE_AUGUST_2026.md). Stock-clamping
 * behaviour (both add() and update()) has its own dedicated tests below,
 * separate from this pre-existing coverage.
 */
#[Test]
final class CartControllerTest
{
    private const string CART_ADD_URI = '/shop/cart/add';
    private const string CART_PATH = '/shop/cart';

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
        ?CatalogQueryService $catalog = null,
    ): CartController {
        /** @var WebViewRenderer&m\MockInterface $renderer */
        $renderer = m::mock(WebViewRenderer::class);
        $renderer->shouldReceive('withControllerName')->andReturnSelf();
        $renderer->shouldReceive('withLayout')->andReturnSelf();

        if ($urlGenerator === null) {
            /** @var UrlGeneratorInterface&m\MockInterface $urlGenerator */
            $urlGenerator = m::mock(UrlGeneratorInterface::class);
            $urlGenerator->shouldReceive('generate')->andReturn(self::CART_PATH)->byDefault();
        }

        $psr17 = new Psr17Factory();

        /** @var StorefrontViewParameters&m\MockInterface $chrome */
        $chrome = m::mock(StorefrontViewParameters::class);

        if ($catalog === null) {
            // update()/remove() both call catalog->find() now (the new
            // stock-clamping check) — stubbed to "product not found" by
            // default so every pre-existing test below (none of which
            // care about stock) keeps its exact original behaviour: a
            // null ProductListing means availableStock is null too, so
            // the new clamp is a no-op. Dedicated stock-clamping tests
            // pass a real $catalog instead.
            /** @var CatalogQueryService&m\MockInterface $catalog */
            $catalog = m::mock(CatalogQueryService::class);
            $catalog->shouldReceive('find')->andReturn(null)->byDefault();
        }

        /** @var TranslatorInterface&m\MockInterface $translator */
        $translator = m::mock(TranslatorInterface::class);
        $translator->shouldReceive('translate')->andReturnUsing(static fn (string $key): string => $key)->byDefault();
        /** @var Flash&m\MockInterface $flash */
        $flash = m::mock(Flash::class);
        $flash->shouldReceive('add')->byDefault();
        $flash->shouldReceive('has')->andReturn(false)->byDefault();

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
            $translator,
            $flash,
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
        $urlGenerator->shouldReceive('generate')->with('shop/cart/index', [], [], null)->andReturn(self::CART_PATH);

        $request = new Psr17Factory()
            ->createServerRequest('POST', '/shop/cart/update')
            ->withParsedBody(['product_id' => '1', 'quantity' => '4']);

        $response = $this->controller($cartService, $urlGenerator)->update($request);

        Assert::same(302, $response->getStatusCode());
        Assert::same(self::CART_PATH, $response->getHeaderLine('Location'));
    }

    private function listing(int $id, float $price, ?float $availableStock): ProductListing
    {
        return new ProductListing(
            id: $id,
            sku: null,
            name: 'Widget',
            description: null,
            price: $price,
            unit: null,
            imageUrl: null,
            family: null,
            category: null,
            subcategory: null,
            availableStock: $availableStock,
        );
    }

    private function catalogFinding(ProductListing $listing): CatalogQueryService
    {
        /** @var CatalogQueryService&m\MockInterface $catalog */
        $catalog = m::mock(CatalogQueryService::class);
        $catalog->shouldReceive('find')->with($listing->id)->andReturn($listing);
        return $catalog;
    }

    public function addClampsToAvailableStockAndFlashesAWarning(): void
    {
        $catalog = $this->catalogFinding($this->listing(1, 9.99, availableStock: 3.0));
        $cartService = new CartService($this->fakeSession());

        /** @var Flash&m\MockInterface $flash */
        $flash = m::mock(Flash::class);
        $flash->shouldReceive('has')->andReturn(false);
        $flash->shouldReceive('add')->once();

        $request = new Psr17Factory()
            ->createServerRequest('POST', self::CART_ADD_URI)
            ->withParsedBody(['product_id' => '1', 'quantity' => '5']);

        $controller = $this->controllerWithFlash($cartService, $catalog, $flash);
        $controller->add($request);

        Assert::same(3.0, $cartService->getItems()[0]->quantity);
    }

    public function addDoesNotClampOrFlashWhenRequestedQuantityFitsWithinStock(): void
    {
        $catalog = $this->catalogFinding($this->listing(1, 9.99, availableStock: 10.0));
        $cartService = new CartService($this->fakeSession());

        /** @var Flash&m\MockInterface $flash */
        $flash = m::mock(Flash::class);
        $flash->shouldNotReceive('add');

        $request = new Psr17Factory()
            ->createServerRequest('POST', self::CART_ADD_URI)
            ->withParsedBody(['product_id' => '1', 'quantity' => '5']);

        $controller = $this->controllerWithFlash($cartService, $catalog, $flash);
        $controller->add($request);

        Assert::same(5.0, $cartService->getItems()[0]->quantity);
    }

    public function addDoesNotClampWhenStockIsUntracked(): void
    {
        $catalog = $this->catalogFinding($this->listing(1, 9.99, availableStock: null));
        $cartService = new CartService($this->fakeSession());

        $request = new Psr17Factory()
            ->createServerRequest('POST', self::CART_ADD_URI)
            ->withParsedBody(['product_id' => '1', 'quantity' => '500']);

        $this->controller($cartService, catalog: $catalog)->add($request);

        Assert::same(500.0, $cartService->getItems()[0]->quantity);
    }

    public function updateClampsToAvailableStockAndReportsItAsClampedInJson(): void
    {
        $catalog = $this->catalogFinding($this->listing(1, 9.99, availableStock: 2.0));
        $cartService = new CartService($this->fakeSession());
        $cartService->add(1, 'Widget', 9.99, 1.0);

        $request = $this->jsonRequest()->withParsedBody(['product_id' => '1', 'quantity' => '10']);
        $response = $this->controller($cartService, catalog: $catalog)->update($request);
        $data = $this->decode($response);

        Assert::same(2.0, (float) $data['quantity']);
        Assert::true($data['clamped']);
    }

    public function updateReportsNotClampedWhenRequestedQuantityFitsWithinStock(): void
    {
        $catalog = $this->catalogFinding($this->listing(1, 9.99, availableStock: 10.0));
        $cartService = new CartService($this->fakeSession());
        $cartService->add(1, 'Widget', 9.99, 1.0);

        $request = $this->jsonRequest()->withParsedBody(['product_id' => '1', 'quantity' => '4']);
        $response = $this->controller($cartService, catalog: $catalog)->update($request);
        $data = $this->decode($response);

        Assert::same(4.0, (float) $data['quantity']);
        Assert::false($data['clamped']);
    }

    private function controllerWithFlash(CartService $cartService, CatalogQueryService $catalog, Flash $flash): CartController
    {
        /** @var WebViewRenderer&m\MockInterface $renderer */
        $renderer = m::mock(WebViewRenderer::class);
        $renderer->shouldReceive('withControllerName')->andReturnSelf();
        $renderer->shouldReceive('withLayout')->andReturnSelf();
        /** @var UrlGeneratorInterface&m\MockInterface $urlGenerator */
        $urlGenerator = m::mock(UrlGeneratorInterface::class);
        $urlGenerator->shouldReceive('generate')->andReturn(self::CART_PATH);
        $psr17 = new Psr17Factory();
        /** @var StorefrontViewParameters&m\MockInterface $chrome */
        $chrome = m::mock(StorefrontViewParameters::class);
        /** @var TranslatorInterface&m\MockInterface $translator */
        $translator = m::mock(TranslatorInterface::class);
        $translator->shouldReceive('translate')->andReturnUsing(static fn (string $key): string => $key);

        return new CartController(
            $renderer,
            new WebControllerService($psr17, $psr17, $urlGenerator),
            $cartService,
            new JsonResponseFactory($psr17, new JsonFormatter()),
            $this->currencyContext(null, CurrencyPreferenceService::NATIVE),
            $chrome,
            $catalog,
            $translator,
            $flash,
        );
    }
}
