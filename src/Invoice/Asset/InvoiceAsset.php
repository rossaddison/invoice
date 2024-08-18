<?php

declare(strict_types=1);

namespace App\Invoice\Asset;

use Yiisoft\Assets\AssetBundle;

class InvoiceAsset extends AssetBundle
{
    public ?string $basePath = '@assets';

    public ?string $baseUrl = '@assetsUrl';

    public ?string $sourcePath = '@src/Invoice/Asset';
    
    public array $css = [
        'invoice/css/style.css',
        'yii3i/yii3i.css', 
        // Upgraded from 1.13.3 to 1.14.0 on 2024/08/05
        'jquery-ui-1.14.0/jquery-ui.min.css',
        'jquery-ui-1.14.0/jquery-ui.structure.min.css',
        'jquery-ui-1.14.0/jquery-ui.theme.min.css',
        
        // bootstrapicons
        '//cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.min.css',        
        '//cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css',
        
        //'//cdn.jsdelivr.net/npm/select2@4.1.0-beta.1/dist/css/select2.min.css'
        'rebuild/css/select2.min.css',
        
        // Automatic asterisk * for required form fields
        'rebuild/css/form.css',
    ];

    public array $js = [
        /**
         * As of 13/08/2024:
         * Modals use the following file which is available in unminified form
         * @see e.g. https:\\code.jquery.com/jquery-4.0.0-beta.2.min.js
         * Modals use the following file which is available in minified form
         * @see e.g. https:\\code.jquery.com/jquery-4.0.0-beta.2.min.js 
         */ 
        
        // e.g. the settings tabs with general, invoice, quote etc depend on this file and also
        // the create quote and create invoice buttons on the client view
        // Renamed the dependencies.js file to a more specific name and load with lastest using
        // https:\\code.jquery.com/{latest file} which is below:        
        'rebuild/js/jquery-4.0.0-beta.2.min.js',
        
        //'//cdn.jsdelivr.net/npm/select2@4.1.0-beta.1/dist/js/select2.min.js',
        'rebuild/js/select2.min.js',        
        'rebuild/js/quote.js',
        'rebuild/js/inv.js',
        'rebuild/js/salesorder.js',
        'rebuild/js/client.js',
        'rebuild/js/product.js',
        'rebuild/js/setting.js',
        'rebuild/js/emailtemplate.js',
        'rebuild/js/scripts.js',
        'rebuild/js/mailer_ajax_email_addresses.js',
        'rebuild/js/modal-product-lookups.js',
        'rebuild/js/modal-task-lookups-inv.js',
        // Upgraded from 1.13.3 to 1.14.0 on 2024/08/05
        'jquery-ui-1.14.0/jquery-ui.min.js',
        
        // bootstrap lightbox
        '//cdn.jsdelivr.net/npm/bs5-lightbox@1.8.3/dist/index.bundle.min.js',        
        '//cdn.jsdelivr.net/npm/clipboard@2.0.11/dist/clipboard.min.js', 
    ];
}
