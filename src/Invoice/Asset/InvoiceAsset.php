<?php

declare(strict_types=1);

namespace App\Invoice\Asset;

use Yiisoft\Assets\AssetBundle;

class InvoiceAsset extends AssetBundle
{
    public ?string $sourcePath = '@src/Invoice/Asset';

    public ?string $basePath = '@assets';

    public ?string $baseUrl = '@assetsUrl';

    /** @psalm-suppress NonInvariantDocblockPropertyType */
    public array $css = [
        'invoice/css/style.css',
        'yii3i/yii3i.css',

        // bootstrapicons
        '//cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.min.css',
        '//cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css',

        //'//cdn.jsdelivr.net/npm/select2@4.1.0-beta.1/dist/css/select2.min.css'
        'rebuild/css/select2.min.css',

        // Automatic asterisk * for required form fields
        'rebuild/css/form.css',
    ];

    /** @psalm-suppress NonInvariantDocblockPropertyType */
    public array $js = [
        /**
         * As of October 2025:
         *
         * The .js files have been converted to vanilla JavaScript.
         * jQuery has been removed from the project.
         */

        //'//cdn.jsdelivr.net/npm/select2@4.1.0-beta.1/dist/js/select2.min.js',
        'rebuild/js/select2.min.js',
        'rebuild/js/quote.js',
        'rebuild/js/inv.js',
        'rebuild/js/salesorder.js',
        'rebuild/js/client.js',
        'rebuild/js/family.js',
        'rebuild/js/product.js',
        'rebuild/js/setting.js',
        'rebuild/js/emailtemplate.js',
        'rebuild/js/scripts.js',
        'rebuild/js/mailer_ajax_email_addresses.js',
        'rebuild/js/modal-product-lookups.js',
        'rebuild/js/modal-task-lookups-inv.js',

        // bootstrap lightbox
        '//cdn.jsdelivr.net/npm/bs5-lightbox@1.8.3/dist/index.bundle.min.js',
        '//cdn.jsdelivr.net/npm/clipboard@2.0.11/dist/clipboard.min.js',
    ];
}
