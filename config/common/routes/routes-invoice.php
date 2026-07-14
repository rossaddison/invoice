<?php

declare(strict_types=1);

use App\Auth\Permissions;
use App\Invoice\InvoiceController as ICLR;
use App\Middleware\RoutePermission;
use Yiisoft\Router\Route;

return [
    RoutePermission::invoiceGroup(

// *********************************** pEI's *********************************
            Route::get('')
                ->action([ICLR::class, 'index'])
                ->name('invoice/index'),

            // Step 1: After https://app.storecove.com/en/docs 1.1.1
            // and 1.1.2 completed
            Route::get('/store_cove_call_api')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([ICLR::class, 'storeCoveCallApi'])
                ->name('invoice/storeCoveCallApi'),

            // Step 2 - 1.1.4 a
            Route::get('/store_cove_call_api_get_legal_entity_id')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([ICLR::class,
                    'storeCoveCallApiGetLegalEntityId'])
                ->name('invoice/storeCoveCallApiGetLegalEntityId'),

            // Step 3a and/or LEGAL entity identifier - 1.1.4 b
            Route::get('/store_cove_call_api_legal_entity_identifier')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([ICLR::class,
                    'storeCoveCallApiLegalEntityIdentifier'])
                ->name('invoice/storeCoveCallApiLegalEntityIdentifier'),

            // Step 4 - 1.1.5
            Route::get('/store_cove_send_test_json_invoice')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([ICLR::class, 'storeCoveSendTestJsonInvoice'])
                ->name('invoice/storeCoveSendTestJsonInvoice'),

            Route::get('/dashboard')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([ICLR::class, 'dashboard'])
                ->name('invoice/dashboard'),

            Route::get('/faq/{topic}')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([ICLR::class, 'faq'])
                ->name('invoice/faq'),

            Route::get('/debug/logs')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([ICLR::class, 'debugLogs'])
                ->name('invoice/debug/logs'),

            Route::get('/requirements')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([ICLR::class, 'requirements'])
                ->name('invoice/requirements'),

            Route::get('/test_data_remove')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([ICLR::class, 'testDataRemove'])
                ->name('invoice/testDataRemove'),

            Route::get('/test_data_reset')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([ICLR::class, 'testDataReset'])
                ->name('invoice/testDataReset'),

            Route::get('/setting_reset')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([ICLR::class, 'settingReset'])
                ->name('invoice/settingReset'),
        ), // invoice
];
