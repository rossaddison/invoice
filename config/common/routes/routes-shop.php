<?php

declare(strict_types=1);

use App\Middleware\RateLimiter;
use App\Middleware\WebshopAvailabilityMiddleware;
use App\Webshop\Cart\CartController;
use App\Webshop\Catalog\ProductsController;
use App\Webshop\Checkout\CheckoutController;
use App\Webshop\Currency\CurrencyController;
use App\Webshop\Delivery\DeliveryController;
use Yiisoft\Router\Group;
use Yiisoft\Router\Route;

/**
 * The public, unauthenticated `/shop` storefront — the in-process
 * replacement for the standalone `rossaddison/webshop` app (see
 * docs/WEBSHOP_INPROCESS_MERGE_AUGUST_2026.md). Deliberately a sibling
 * top-level group, NOT nested inside `Group::create('/{_language}')`
 * (config/common/di/router.php wraps every OTHER route file in that
 * automatically) — same shape as this app's other already-public routes
 * (`/go/{key}`, `/scan/{token}`), and it sidesteps the `_language`-
 * argument-passing bug class `App\Api\OrderService`'s own docblock
 * documents entirely for links *within* `/shop` itself: none of these
 * route names ever need `_language` passed to `generate()`. Linking
 * *out* to an `/{_language}`-group route (e.g. `inv/view`, `inv/guest`,
 * `auth/login` — see the storefront layout) still needs it passed
 * explicitly, same as ever.
 *
 * None of these routes carry `RoutePermission::invoiceGroup()`/
 * `RoutePermission::check()` — that RBAC gate is for the staff-facing
 * app; a storefront visitor has no session to check permissions against
 * at all until checkout logs one in (`OrderService::logInOrderUser()`).
 *
 * `WebshopAvailabilityMiddleware` on the whole group below is a
 * different kind of gate — not RBAC, an on/off switch: it 404s every
 * `/shop/*` route while the `no_front_webshop_page` setting is on (see
 * that middleware's own docblock for why the setting previously only hid
 * the navbar link, not the routes themselves).
 */
return [
    Group::create('/shop')
        ->middleware(WebshopAvailabilityMiddleware::class)
        ->routes(
            // '' not '/' — Group prefixes concatenate the pattern literally
            // ('/shop' . '/' would only match /shop/, not the canonical
            // trailing-slash-free /shop; confirmed live).
            Route::get('')
                ->action([ProductsController::class, 'index'])
                ->name('shop/catalog/index'),
            Route::get('/products/{id:\d+}')
                ->action([ProductsController::class, 'show'])
                ->name('shop/catalog/show'),
            Route::get('/cart')
                ->action([CartController::class, 'index'])
                ->name('shop/cart/index'),
            Route::post('/cart/add')
                ->middleware(RateLimiter::perIp(30, 'shop_cart_add'))
                ->action([CartController::class, 'add'])
                ->name('shop/cart/add'),
            Route::post('/cart/update')
                ->middleware(RateLimiter::perIp(30, 'shop_cart_update'))
                ->action([CartController::class, 'update'])
                ->name('shop/cart/update'),
            Route::post('/cart/remove/{id:\d+}')
                ->middleware(RateLimiter::perIp(30, 'shop_cart_remove'))
                ->action([CartController::class, 'remove'])
                ->name('shop/cart/remove'),
            Route::post('/delivery-address')
                ->action([DeliveryController::class, 'update'])
                ->name('shop/delivery-address'),
            Route::post('/currency')
                ->action([CurrencyController::class, 'update'])
                ->name('shop/currency'),
            // The "Refresh now" button next to "Change currency" — only
            // rendered when auto_update_exchange_rate is on. Rate-limited
            // like every other public mutating /shop route, even though
            // ExchangeRateUpdateService::updateIfDue() is already
            // effectively harmless to call repeatedly (see
            // CurrencyController::refreshRate()'s own docblock).
            Route::post('/currency/refresh-rate')
                ->middleware(RateLimiter::perIp(5, 'shop_currency_refresh_rate'))
                ->action([CurrencyController::class, 'refreshRate'])
                ->name('shop/currency/refresh-rate'),
            Route::get('/checkout')
                ->action([CheckoutController::class, 'index'])
                ->name('shop/checkout/index'),
            // Same path as the GET route above (index) — method alone
            // distinguishes them, same shape as the standalone webshop app's
            // own /checkout route pair. Rate-limited like /homecare-signup's
            // own guest-account-creation routes (routes-homecare-signup.php)
            // — this route also creates a real Client + User on success.
            Route::post('/checkout')
                ->middleware(RateLimiter::global(50, 10))
                ->middleware(RateLimiter::perIp(5, 'shop_checkout_submit'))
                ->action([CheckoutController::class, 'submit'])
                ->name('shop/checkout/submit'),
        ),
];
