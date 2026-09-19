<?php

declare(strict_types=1);

use App\Auth\Permissions;
use App\Bookkeeping\Controller\BookkeepingExportController;
use App\Bookkeeping\Controller\QuickBooksConnectController;
use App\Invoice\PaymentInformation\AdyenHmacKeyVerificationController;
use App\Invoice\Setting\SettingController;
use App\Middleware\RoutePermission;
use Yiisoft\Http\Method;
use Yiisoft\Router\Route;

return [
    RoutePermission::invoiceGroup(
        Route::get('/setting/debug_index[/page{page:\d+}]')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([SettingController::class, 'debugIndex'])
                ->name('setting/debugIndex'),
        Route::methods([Method::GET, Method::POST], '/setting/save')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([SettingController::class, 'save'])
                ->name('setting/save'),
        Route::methods([Method::GET, Method::POST], '/setting/tab_index[/{active:\d+}]')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([SettingController::class, 'tabIndex'])
                ->name('setting/tabIndex'),
        Route::methods([Method::GET, Method::POST], '/setting/add')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([SettingController::class, 'add'])
                ->name('setting/add'),
        Route::methods([Method::GET, Method::POST], '/setting/edit/{setting_id}')
                ->name('setting/edit')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([SettingController::class, 'edit']),
        Route::methods([Method::GET, Method::POST], '/setting/delete/{setting_id}')
                ->name('setting/delete')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([SettingController::class, 'delete']),
        Route::get('/setting/fphgenerate')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([SettingController::class, 'fphgenerate'])
                ->name('setting/fphgenerate'),
        Route::get('/setting/checkPhpVersion')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([SettingController::class, 'checkPhpVersionNow'])
                ->name('setting/checkPhpVersion'),
        Route::get('/setting/downloadBackup')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([SettingController::class, 'downloadBackup'])
                ->name('setting/downloadBackup'),
        Route::methods([Method::GET, Method::POST], '/setting/index')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([SettingController::class, 'index'])
                ->name('setting/index'),
        Route::methods([Method::GET, Method::POST], '/setting/getCronKey')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([SettingController::class, 'getCronKey'])
                ->name('setting/getCronKey'),
        Route::methods([Method::GET, Method::POST], '/setting/view/{setting_id}')
                ->name('setting/view')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([SettingController::class, 'view']),
        Route::methods([Method::GET, Method::POST], '/setting/clear')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([SettingController::class, 'clear'])
                ->name('setting/clear'),
        Route::get('/setting/adyenVerifyHmacKey')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([AdyenHmacKeyVerificationController::class, 'verifyHmacKey'])
                ->name('setting/adyenVerifyHmacKey'),
        // Not nested under Group::create('/{_language}') -- deliberately,
        // matching every payment-gateway webhook/complete route in
        // routes-payment-information.php: the callback's redirect_uri is
        // a single fixed URL registered in the Intuit app dashboard, and
        // generateAbsolute() needs no _language argument for a route
        // that was never under that group to begin with.
        Route::get('/bookkeeping/quickbooksConnect')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([QuickBooksConnectController::class, 'connect'])
                ->name('bookkeeping/quickbooksConnect'),
        Route::get('/bookkeeping/quickbooksCallback')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([QuickBooksConnectController::class, 'callback'])
                ->name('bookkeeping/quickbooksCallback'),
        Route::post('/bookkeeping/export')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([BookkeepingExportController::class, 'export'])
                ->name('bookkeeping/export'),
    ), // invoice
];
