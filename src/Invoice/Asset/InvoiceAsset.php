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
        // Upgraded from 1.13.2 to 1.13.3 on 2024/06/15
        'jquery-ui-1.13.3/jquery-ui.min.css',
        'jquery-ui-1.13.3/jquery-ui.structure.min.css',
        'jquery-ui-1.13.3/jquery-ui.theme.min.css',
        
        // bootstrapicons
        '//cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.min.css',        
        '//cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css',
        
        //'//unpkg.com/dropzone@5/dist/min/dropzone.min.css',
        'rebuild-1.13/css/dropzone.5.min.css',
        
        //'//cdn.jsdelivr.net/npm/select2@4.1.0-beta.1/dist/css/select2.min.css'
        'rebuild-1.13/css/select2.min.css',
        
        // Automatic asterisk * for required form fields
        'rebuild-1.13/css/form.css',
    ];

    public array $js = [  
        // Upgraded from 3.6.0 to 3.7.1 on 2024/06/15
        'rebuild-1.13/js/jquery-3.7.1.min.js',
        
        //modals use the following file which is available in unminified form
        // e.g. the settings tabs with general, invoice, quote etc depend on this file
        'rebuild-1.13/js/dependencies.min.js',
        
        //'//unpkg.com/dropzone@5/dist/min/dropzone.min.js',
        'rebuild-1.13/js/dropzone.5.min.js',
        //'//cdn.jsdelivr.net/npm/select2@4.1.0-beta.1/dist/js/select2.min.js',
        'rebuild-1.13/js/select2.min.js',        
        'rebuild-1.13/js/quote.js',
        'rebuild-1.13/js/inv.js',
        'rebuild-1.13/js/salesorder.js',
        'rebuild-1.13/js/client.js',
        'rebuild-1.13/js/product.js',
        'rebuild-1.13/js/setting.js',
        'rebuild-1.13/js/emailtemplate.js',
        'rebuild-1.13/js/scripts.js',
        'rebuild-1.13/js/client_custom_fields.js',
        'rebuild-1.13/js/mailer_ajax_email_addresses.js',
        'rebuild-1.13/js/modal-product-lookups.js',
        'rebuild-1.13/js/modal-task-lookups-inv.js',
        //'rebuild-1.13/js/dropzone-quote-scripts.js', 
        // Upgraded from 1.13.2 to 1.13.3 on 2024/06/15
        'jquery-ui-1.13.3/jquery-ui.min.js',
        
        // bootstrap lightbox
        '//cdn.jsdelivr.net/npm/bs5-lightbox@1.8.3/dist/index.bundle.min.js',        
        '//cdn.jsdelivr.net/npm/clipboard@2.0.11/dist/clipboard.min.js',
    ];
}
