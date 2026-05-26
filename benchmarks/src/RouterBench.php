<?php

declare(strict_types=1);

/**
 * Router Benchmark Suite
 *
 * Measures Yiisoft\Router\FastRoute\UrlMatcher — the URL-dispatch layer
 * backed by nikic/fast-route.
 *
 * The route table below mirrors a realistic slice of the Yii3-i route
 * definition, with static routes, parameter routes, and a large trailing
 * block to exercise worst-case matching.
 *
 * @return array<string, array{fn:callable, revs:int, warmup:int, its:int}>
 */

use HttpSoft\Message\ServerRequest;
use Yiisoft\Router\FastRoute\UrlMatcher;
use Yiisoft\Router\Route;
use Yiisoft\Router\RouteCollection;
use Yiisoft\Router\RouteCollector;

return static function (): array {
    // ── Build the route collection ─────────────────────────────────────────────
    $collector = new RouteCollector();

    $noop = static fn() => null;

    $routes = [
        // Top-level static routes (hit early in dispatcher)
        Route::get('/'),
        Route::get('/login'),
        Route::post('/login'),
        Route::get('/logout'),
        Route::get('/signup'),
        Route::post('/signup'),

        // Invoice module (parametrised)
        Route::get('/invoice'),
        Route::get('/invoice/create'),
        Route::post('/invoice/create'),
        Route::get('/invoice/view/{id:\d+}'),
        Route::get('/invoice/update/{id:\d+}'),
        Route::post('/invoice/update/{id:\d+}'),
        Route::get('/invoice/delete/{id:\d+}'),
        Route::post('/invoice/delete/{id:\d+}'),
        Route::get('/invoice/pdf/{id:\d+}'),
        Route::get('/invoice/email/{id:\d+}'),
        Route::post('/invoice/email/{id:\d+}'),

        // Quote module
        Route::get('/quote'),
        Route::get('/quote/create'),
        Route::post('/quote/create'),
        Route::get('/quote/view/{id:\d+}'),
        Route::get('/quote/update/{id:\d+}'),
        Route::post('/quote/update/{id:\d+}'),
        Route::get('/quote/delete/{id:\d+}'),

        // Client module
        Route::get('/client'),
        Route::get('/client/create'),
        Route::post('/client/create'),
        Route::get('/client/view/{id:\d+}'),
        Route::get('/client/update/{id:\d+}'),
        Route::post('/client/update/{id:\d+}'),

        // Product module
        Route::get('/product'),
        Route::get('/product/create'),
        Route::post('/product/create'),
        Route::get('/product/view/{id:\d+}'),
        Route::get('/product/update/{id:\d+}'),
        Route::post('/product/update/{id:\d+}'),

        // Settings
        Route::get('/setting'),
        Route::post('/setting/tab/{tab}'),
        Route::get('/setting/tax'),
        Route::post('/setting/tax'),

        // Dashboard, reports
        Route::get('/dashboard'),
        Route::get('/report'),
        Route::get('/report/invoice-aging'),
        Route::get('/report/revenue/{year:\d{4}}'),

        // API endpoints
        Route::get('/api/v1/invoice/{id:\d+}'),
        Route::post('/api/v1/invoice'),
        Route::get('/api/v1/client/{id:\d+}'),
        Route::post('/api/v1/client'),

        // Trailing routes — the target is near the bottom to test worst-case
        Route::get('/admin'),
        Route::get('/admin/users'),
        Route::get('/admin/roles'),
        Route::get('/admin/permissions'),
        Route::get('/admin/log'),
        Route::get('/admin/log/view/{id:\d+}'),
    ];

    foreach ($routes as $route) {
        $collector->addRoute($route->action($noop));
    }

    $routeCollection = new RouteCollection($collector);
    $matcher         = new UrlMatcher($routeCollection);

    // ── Pre-built PSR-7 request objects ────────────────────────────────────────
    $reqRoot       = new ServerRequest(method: 'GET', uri: '/');
    $reqInvoice    = new ServerRequest(method: 'GET', uri: '/invoice');          // static, mid-table
    $reqParam      = new ServerRequest(method: 'GET', uri: '/invoice/view/42'); // parametrised
    $reqDeepParam  = new ServerRequest(method: 'GET', uri: '/report/revenue/2025'); // named + regex
    $reqAdminLog   = new ServerRequest(method: 'GET', uri: '/admin/log/view/7'); // near-last, param
    $req404        = new ServerRequest(method: 'GET', uri: '/does/not/exist');   // no match

    return [
        // ── Static route, first in table (best case) ─────────────────────────
        'benchMatchRoot' => [
            'fn'     => static fn() => $matcher->match($reqRoot),
            'revs'   => 1000,
            'warmup' => 3,
            'its'    => 7,
        ],

        // ── Static route, middle of table ────────────────────────────────────
        'benchMatchStaticMid' => [
            'fn'     => static fn() => $matcher->match($reqInvoice),
            'revs'   => 1000,
            'warmup' => 3,
            'its'    => 7,
        ],

        // ── Parametrised route {id:\d+} ───────────────────────────────────────
        'benchMatchParametrised' => [
            'fn'     => static fn() => $matcher->match($reqParam),
            'revs'   => 1000,
            'warmup' => 3,
            'its'    => 7,
        ],

        // ── Deep parametrised + named segment ────────────────────────────────
        'benchMatchDeepParam' => [
            'fn'     => static fn() => $matcher->match($reqDeepParam),
            'revs'   => 1000,
            'warmup' => 3,
            'its'    => 7,
        ],

        // ── Near-last route in table, parametrised (worst case) ──────────────
        'benchMatchWorstCase' => [
            'fn'     => static fn() => $matcher->match($reqAdminLog),
            'revs'   => 1000,
            'warmup' => 3,
            'its'    => 7,
        ],

        // ── 404 — dispatcher must exhaust the full table ──────────────────────
        'benchMatch404' => [
            'fn'     => static fn() => $matcher->match($req404),
            'revs'   => 1000,
            'warmup' => 3,
            'its'    => 7,
        ],
    ];
};
