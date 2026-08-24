<?php

declare(strict_types=1);

namespace App\Webshop\Catalog;

use App\Service\WebControllerService;
use App\Webshop\Controller\StorefrontController;
use App\Webshop\StorefrontViewParameters;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Yiisoft\Router\HydratorAttribute\RouteArgument;
use Yiisoft\Yii\View\Renderer\WebViewRenderer;

final class ProductsController extends StorefrontController
{
    public function __construct(
        WebViewRenderer $webViewRenderer,
        private readonly WebControllerService $webService,
        private readonly CatalogQueryService $catalog,
        private readonly StorefrontViewParameters $chrome,
    ) {
        parent::__construct($webViewRenderer, 'shop/catalog');
    }

    public function index(ServerRequestInterface $request): ResponseInterface
    {
        $allProducts = $this->catalog->listAll();
        $filter = ProductFilter::fromQueryParams($request->getQueryParams());

        $prices = array_map(static fn (ProductListing $p): float => $p->price, $allProducts);

        return $this->render('index', [
            ...$this->chrome->getLayoutParameters(),
            'products' => $filter->apply($allProducts),
            'filter' => $filter,
            'categoryOptions' => $this->distinctValues($allProducts, static fn (ProductListing $p): ?string => $p->category),
            'subcategoryOptions' => $this->distinctValues($allProducts, static fn (ProductListing $p): ?string => $p->subcategory),
            'familyOptions' => $this->distinctValues($allProducts, static fn (ProductListing $p): ?string => $p->family),
            // The full catalog's own price range — shown as the input
            // fields' placeholder text, so the customer knows what range
            // is even worth typing before they've filtered anything.
            'catalogMinPrice' => $prices === [] ? null : min($prices),
            'catalogMaxPrice' => $prices === [] ? null : max($prices),
        ]);
    }

    /**
     * Facet checkbox options are always built from the *full* unfiltered
     * catalog, not the filtered result — so narrowing by Category never
     * makes a Family checkbox disappear, letting the customer broaden the
     * filter back out again.
     *
     * @param list<ProductListing> $products
     * @param callable(ProductListing): ?string $field
     * @return list<string>
     */
    private function distinctValues(array $products, callable $field): array
    {
        $values = [];
        foreach ($products as $product) {
            $value = $field($product);
            if ($value !== null) {
                $values[$value] = true;
            }
        }

        $distinct = array_keys($values);
        sort($distinct);
        return $distinct;
    }

    public function show(
        ServerRequestInterface $request,
        #[RouteArgument('id')] int $id,
    ): ResponseInterface {
        $product = $this->catalog->find($id);
        if ($product === null) {
            return $this->webService->getNotFoundResponse();
        }

        // Set only when this page was reached from the cart/checkout
        // "Add something else" gallery (see resources/views/shop/_shared/
        // product_gallery.php) — carried through to the Add-to-cart
        // form's own hidden `redirect` field (shop/catalog/view.php) so
        // App\Webshop\Cart\CartController::add() can send the customer
        // back to whichever of those two pages they actually came from,
        // instead of always landing on the cart page.
        /** @var mixed $redirect */
        $redirect = $request->getQueryParams()['redirect'] ?? null;

        return $this->render('view', [
            ...$this->chrome->getLayoutParameters(),
            'product' => $product,
            'redirect' => is_string($redirect) ? $redirect : null,
        ]);
    }
}
