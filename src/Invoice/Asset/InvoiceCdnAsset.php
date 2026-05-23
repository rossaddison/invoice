<?php

declare(strict_types=1);

namespace App\Invoice\Asset;

use App\Invoice\Asset\Bootstrap5LightBoxCdnAsset;
use App\Asset\ClipBoardCdnAsset;
use Yiisoft\Assets\AssetBundle;

class InvoiceCdnAsset extends AssetBundle
{
    public ?string $sourcePath = '@src/Invoice/Asset';

    public ?string $basePath = '@assets';

    public ?string $baseUrl = '@assetsUrl';

    /** @psalm-suppress NonInvariantDocblockPropertyType */
    public array $css = [
        // 1. CSS custom properties — must load first (used by all files below)
        'invoice/css/variables.css',

        // 2. Normalize / base typography
        'invoice/css/base.css',

        // 3. Bootstrap 5 + InvoicePlane core (compiled from SCSS)
        'invoice/css/style.css',

        // 4. Layout rules
        'invoice/css/layout.css',

        // 5. Component styles (cards, buttons, status labels)
        'invoice/css/components.css',

        // 6. Utility classes
        'invoice/css/utilities.css',

        // 7. Yii3i supplementary styles
        'yii3i/yii3i.css',

        // 8. Required form field asterisk styles
        'rebuild/css/form.css',

        // 9. Toolbar widget styles
        'rebuild/css/buttons-toolbar.css',

        // 10. Quote page toolbar styles
        'rebuild/css/quote-toolbar.css',

        // 11. Dark-mode / theme overrides — must be last
        'invoice/css/overrides.css',
    ];

    /** @psalm-suppress NonInvariantDocblockPropertyType */
    public array $js = [

        // TypeScript compiled bundle (IIFE format) - contains
        // quote, inv, salesorder, client, family, product, productclient, task,
        // setting, scripts, modal-product-lookups, modal-task-lookups
        'rebuild/js/invoice-typescript-iife.js',

        'rebuild/js/cron.js',
        'rebuild/js/emailtemplate.js',
        'rebuild/js/mailer_ajax_email_addresses.js',
    ];

    public bool $cdn = false;

    public array $depends = [
        Bootstrap5LightBoxCdnAsset::class,
        ClipBoardCdnAsset::class,
    ];
}
