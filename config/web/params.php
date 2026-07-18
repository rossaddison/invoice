<?php

declare(strict_types=1);

use Yiisoft\Bootstrap5\Assets\BootstrapAsset;
use Yiisoft\Bootstrap5\Assets\BootstrapCdnAsset;
use App\Invoice\Prometheus\PrometheusMiddleware;
use Yiisoft\Cookies\CookieMiddleware;
use Yiisoft\ErrorHandler\Middleware\ErrorCatcher;
use Yiisoft\RequestProvider\RequestCatcherMiddleware;
use Yiisoft\Router\Middleware\Router;
use Yiisoft\Session\SessionMiddleware;
use Yiisoft\User\Login\Cookie\CookieLoginMiddleware;
use App\Middleware\ContentSecurityPolicyMiddleware;
use App\Middleware\CsrfExemptMiddleware;
use App\Middleware\PageOutOfRangeMiddleware;
use App\Middleware\SecurityHeadersMiddleware;
use Yiisoft\Yii\Middleware\Locale;

// yii3-i
// Amazon Pay serves its SDK, button graphics, and checkout iframe from this
// wildcard domain — referenced across four separate CSP directives below.
$amazonPayCsp = ' https://*.payments-amazon.com';

return [
    'locale' => [
        'locales' => [
            /**
             * Note: key affects RouteArgument _language, value matches locale
             * Related logic: see key => value
             */
            'af-ZA' => 'af-ZA',
            'ar-BH' => 'ar-BH',
            'az' => 'az-AZ',
            'be-BY' => 'be-BY',
            'bs' => 'bs-BS',
            'de' => 'de-DE',
            'en' => 'en-US',
            'es' => 'es-ES',
            'fil' => 'fil-PH',
            'fr' => 'fr-FR',
            'gd-GB' => 'gd-GB',
            'ha-NG' => 'ha-NG',
            'he-IL' => 'he-IL',
            'ig-NG' => 'ig-NG',
            'id' => 'id-ID',
            'it' => 'it-IT',
            'ja' => 'ja-JP',
            'nl' => 'nl-NL',
            'pl' => 'pl-PL',
            'pt-BR' => 'pt-BR',
            'ru' => 'ru-RU',
            'sk' => 'sk-SK',
            'sl' => 'sl-SL',
            'uk' => 'uk-UA',
            'uz' => 'uz-UZ',
            'vi' => 'vi-VN',
            'yo-NG' => 'yo-NG',
            'zh-CN' => 'zh-CN',
            'zh-TW' => 'zh-TW',
            'zu-ZA' => 'zu-ZA',
        ],
        'ignoredRequests' => [
            '/gii**',
            '/debug**',
            '/inspect**',
        ],
    ],
    'middlewares' => [
        RequestCatcherMiddleware::class,
        ErrorCatcher::class,
        ContentSecurityPolicyMiddleware::class,
        SecurityHeadersMiddleware::class,
        PrometheusMiddleware::class,
        SessionMiddleware::class,
        CsrfExemptMiddleware::class,
        CookieMiddleware::class,
        CookieLoginMiddleware::class,
        Locale::class,
        PageOutOfRangeMiddleware::class,
        Router::class,
    ],

    // Content-Security-Policy directives.
    // Mirrors the policy in public/.htaccess — both headers are sent and browsers
    // apply the intersection, so they must stay in sync.
    'csp' => [
        'policy' => implode('; ', [
            "default-src 'self'",
            "script-src 'self'"
                . " https://apis.google.com"
                . " https://cdn.jsdelivr.net"
                . " https://js.stripe.com"
                . " https://*.stripe.com"
                . $amazonPayCsp
                . " https://assets.braintreegateway.com"
                . " https://js.braintreegateway.com"
                . " https://challenges.cloudflare.com",
            "style-src 'self' 'unsafe-inline'"
                . " https://fonts.googleapis.com"
                . " https://cdn.jsdelivr.net"
                . " https://assets.braintreegateway.com",
            "font-src 'self'"
                . " https://fonts.gstatic.com"
                . " https://cdn.jsdelivr.net",
            "img-src 'self' data: blob:"
                . " https://flagcdn.com"
                . " https://*.stripe.com"
                . " https://assets.braintreegateway.com"
                . " https://s3.amazonaws.com"
                . $amazonPayCsp
                . " https://www.mollie.com",
            "connect-src 'self'"
                . " https://api.storecove.com"
                . " https://api.stripe.com"
                . " https://*.stripe.com"
                . " https://*.braintreegateway.com"
                . $amazonPayCsp
                . " https://challenges.cloudflare.com",
            "frame-src 'self'"
                . " https://js.stripe.com"
                . " https://*.stripe.com"
                . " https://hooks.stripe.com"
                . " https://assets.braintreegateway.com"
                . $amazonPayCsp
                . " https://challenges.cloudflare.com",
            "child-src 'self'"
                . " https://js.stripe.com"
                . " https://*.stripe.com"
                . " https://challenges.cloudflare.com",
            "form-action 'self'",
            "frame-ancestors 'none'",
            "base-uri 'self'",
            "object-src 'none'",
            "manifest-src 'self'",
            "worker-src 'self'",
        ]),
    ],
    // Mirrors the headers in public/.htaccess (both are sent; keep in sync)
    // and adds HSTS + Permissions-Policy, which existed nowhere before —
    // neither PHP nor Apache. Guaranteed here regardless of the web server
    // in front of the app (Apache-only headers vanish on another server, a
    // header-stripping proxy, or PHP's built-in dev server).
    'security-headers' => [
        'headers' => [
            'X-Content-Type-Options' => 'nosniff',
            'X-Frame-Options' => 'DENY',
            'X-XSS-Protection' => '1; mode=block',
            'Referrer-Policy' => 'strict-origin-when-cross-origin',
            // Browsers ignore this header entirely over plain HTTP, so
            // sending it now is safe ahead of the HTTPS redirect being
            // enabled (see docs/SECURITY_HARDENING_AUDIT_JULY_2026.md #2).
            'Strict-Transport-Security' => 'max-age=31536000; includeSubDomains',
            // Deny browser features this app's own code never calls
            // (QR codes are scanned by the visitor's separate camera app,
            // not getUserMedia in-page). Anything not listed keeps the
            // browser default.
            'Permissions-Policy' => 'camera=(), microphone=(), geolocation=(),'
                . ' payment=(), usb=(), magnetometer=(), gyroscope=(), accelerometer=()',
        ],
    ],
    // Overrides yiisoft/session's package default (cookie_secure => 0), but
    // only once SESSION_COOKIE_SECURE is explicitly set — see autoload.php:
    // yiisoft/session throws on every request if this is on and the
    // request scheme isn't https, so this must stay opt-in per deployment.
    'yiisoft/session' => [
        'session' => [
            'options' => [
                'cookie_secure' => (int) $_ENV['SESSION_COOKIE_SECURE'],
            ],
        ],
    ],
    'yiisoft/widget' => [
        'defaultTheme' => 'bootstrap5',
    ],
    'yiisoft/assets' => [
        'assetManager' => [
            'customizedBundles' => [
                // Bootstrap CSS is already compiled into style.css — suppress the
                // duplicate load that yii-bootstrap5 widgets auto-register.
                // main.php uses BootstrapCssOnlyAsset / BootstrapCdnCssOnlyAsset instead.
                BootstrapAsset::class => [
                    'css' => [],
                ],
                BootstrapCdnAsset::class => [
                    'css' => [],
                ],
            ],
        ],
    ],
];
