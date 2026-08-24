<?php

declare(strict_types=1);

namespace App\Webshop;

use App\Invoice\Setting\SettingRepository;
use App\Webshop\Cart\CartService;
use App\Webshop\Currency\CurrencyContext;
use App\Webshop\Delivery\DeliveryAddressService;
use Yiisoft\Session\Flash\Flash;
use Yiisoft\User\CurrentUser;
use Yiisoft\Yii\View\Renderer\LayoutParametersInjectionInterface;

/**
 * The navbar chrome data every storefront page needs (cart badge count,
 * currency widget, delivery widget, login-vs-my-orders nav item) —
 * used two ways, deliberately not overlapping:
 *
 * 1. Each storefront controller constructor-injects this and spreads
 *    `getLayoutParameters()` into its own `render($view, [...])` calls,
 *    since the *view* template itself (e.g. `shop/catalog/index.php`)
 *    references `$currency` directly.
 * 2. It's ALSO registered as a `LayoutParametersInjectionInterface`
 *    injection (`config/common/params.php`'s `yiisoft/yii-view-renderer.
 *    injections`, wrapped in `LayoutSpecificInjections` scoped to
 *    `@views/layout/templates/storefront/main.php`) — the storefront
 *    *layout* is rendered as a genuinely separate pass
 *    (`WebViewRenderer::renderProxy()`'s own `$currentView->
 *    setParameters($layoutParameters)->render($layout, ['content' =>
 *    $content])`), which never sees whatever was passed to the view's
 *    own `render($view, $parameters)` call — confirmed live: the layout
 *    threw `Undefined variable $deliveryAddress` until this injection
 *    was registered, even though every controller was already spreading
 *    the same data into its own view parameters.
 *
 * Both paths resolve this service per-request (controller-constructor
 * autowiring, or `WebViewRenderer`'s own injection resolution during
 * `render()`) — never from a container-build-time factory closure, which
 * is the actual constraint that matters; see
 * `App\Webshop\Currency\CurrencyContext`'s own docblock for the bug that
 * distinction avoids.
 */
final readonly class StorefrontViewParameters implements LayoutParametersInjectionInterface
{
    public function __construct(
        private CartService $cart,
        private CurrencyContext $currencyContext,
        private DeliveryAddressService $deliveryAddressService,
        private CurrentUser $currentUser,
        private Flash $flash,
        // Exposed as 's' — the same variable name/trait-backed instance
        // (App\Invoice\Setting\Trait\SettingTooltipTrait) every
        // partial_settings_*.php admin view already calls
        // $s->infoIcon('some_key') on. The storefront's "Change currency"
        // widget uses it the same way — see SettingTooltipTrait's own
        // 'webshop_currency_preference' entry.
        private SettingRepository $settingRepository,
    ) {
    }

    /** @return array{cartCount: int, currency: CurrencyContext, deliveryAddress: ?\App\Webshop\Delivery\DeliveryAddress, isGuest: bool, flash: Flash, s: SettingRepository} */
    #[\Override]
    public function getLayoutParameters(): array
    {
        return [
            'cartCount' => count($this->cart->getItems()),
            'currency' => $this->currencyContext,
            'deliveryAddress' => $this->deliveryAddressService->get(),
            'isGuest' => $this->currentUser->isGuest(),
            'flash' => $this->flash,
            's' => $this->settingRepository,
        ];
    }
}
