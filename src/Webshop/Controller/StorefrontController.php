<?php

declare(strict_types=1);

namespace App\Webshop\Controller;

use Psr\Http\Message\ResponseInterface as Response;
use Yiisoft\Yii\View\Renderer\WebViewRenderer;

/**
 * Base for every public, unauthenticated `/shop` storefront controller —
 * the merged-in-process replacement for the standalone `rossaddison/webshop`
 * app (see docs/WEBSHOP_INPROCESS_MERGE_AUGUST_2026.md).
 *
 * Deliberately does **not** extend `App\Invoice\BaseController`: that
 * class's `initializeViewRenderer()` unconditionally picks one of three
 * *staff* layouts based on `UserService::hasPermission()` +
 * `session->get('tfa_verified')` — none of which a never-logged-in
 * storefront visitor has any reason to carry. The actual precedent for a
 * public, unauthenticated controller in this app is `App\Controller\
 * SiteController` (plain class, `WebViewRenderer` only) — this follows
 * that same shape, just pulled into a reusable base now that 5 storefront
 * controllers need the same plumbing instead of SiteController's 1.
 *
 * Uses `withControllerName()` rather than `withController($this)` —
 * `WebViewRenderer::extractControllerName()` regex-derives a view
 * directory from the class name (`ProductsController` -> `products`),
 * which doesn't match this app's own `resources/views/shop/{module}/`
 * layout. Every existing multi-word-namespaced controller in this app
 * (`InvController` -> `'invoice/inv'`, `ProductController` ->
 * `'invoice/product'`) already sets `$controllerName` explicitly for the
 * same reason — `'shop/{module}'` here follows that exact convention.
 */
abstract class StorefrontController
{
    private const string LAYOUT = '@views/layout/templates/storefront/main.php';

    protected WebViewRenderer $webViewRenderer;

    protected function __construct(WebViewRenderer $webViewRenderer, string $controllerName)
    {
        $this->webViewRenderer = $webViewRenderer
            ->withControllerName($controllerName)
            ->withLayout(self::LAYOUT);
    }

    /** @param array<string, mixed> $parameters */
    protected function render(string $view, array $parameters = []): Response
    {
        return $this->webViewRenderer->render($view, $parameters);
    }
}
