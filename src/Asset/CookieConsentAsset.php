<?php

declare(strict_types=1);

namespace App\Asset;

use Yiisoft\Assets\AssetBundle;

/**
 * Public-site cookie consent banner's own JS -- CSP-safe (this app's CSP
 * is script-src 'self', no 'unsafe-inline', so the banner's Accept/
 * Decline buttons cannot use inline onclick attributes). See
 * cookie-consent.ts and resources/views/layout/templates/soletrader/
 * main.php, which registers this bundle and renders the banner markup.
 */
final class CookieConsentAsset extends AssetBundle
{
    public ?string $basePath = '@assets';

    public ?string $baseUrl = '@assetsUrl';

    public ?string $sourcePath = '@src/Asset';

    /** @psalm-suppress NonInvariantDocblockPropertyType */
    public array $css = [];

    /** @psalm-suppress NonInvariantDocblockPropertyType */
    public array $js = [
        'rebuild/js/cookie-consent-iife.js',
    ];
}
