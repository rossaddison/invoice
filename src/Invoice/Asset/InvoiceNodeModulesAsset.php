<?php

declare(strict_types=1);

namespace App\Invoice\Asset;

use App\Invoice\Asset\NodeModulesBootstrapLightboxAsset;
use App\Invoice\Asset\NodeModulesClipBoardAsset;
use Yiisoft\Assets\AssetBundle;

class InvoiceNodeModulesAsset extends AssetBundle
{
    public ?string $sourcePath = '@src/Invoice/Asset';

    public ?string $basePath = '@assets';

    public ?string $baseUrl = '@assetsUrl';

    /** @psalm-suppress NonInvariantDocblockPropertyType */
    public array $css = [
        'invoice/css/style.css',
        'invoice/css/overrides.css',
        'yii3i/yii3i.css',

        // Automatic asterisk * for required form fields
        'rebuild/css/form.css',

        // ButtonsToolbar Widget styles
        'rebuild/css/buttons-toolbar.css',

        // QuoteToolbar Widget styles
        'rebuild/css/quote-toolbar.css',
    ];

    public bool $cdn = false;

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

    public array $depends = [
        NodeModulesBootstrapLightboxAsset::class,
        NodeModulesClipBoardAsset::class,
    ];
}
