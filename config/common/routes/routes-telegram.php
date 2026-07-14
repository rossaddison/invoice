<?php

declare(strict_types=1);

use App\Auth\Permissions;
use App\Invoice\Telegram\TelegramController;
use App\Middleware\RoutePermission;
use Yiisoft\Http\Method;
use Yiisoft\Router\Route;

return [
    RoutePermission::invoiceGroup(

            Route::get('/telegram')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([TelegramController::class, 'index'])
                ->name('telegram/index'),

            Route::get('/telegram/deleteWebhook')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([TelegramController::class, 'deleteWebhook'])
                ->name('telegram/deleteWebhook'),

            Route::get('/telegram/getWebhookinfo')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([TelegramController::class, 'getWebhookinfo'])
                ->name('telegram/getWebhookinfo'),

            Route::get('/telegram/setWebhook')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([TelegramController::class, 'setWebhook'])
                ->name('telegram/setWebhook'),

            Route::get('/telegram/getUpdates')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([TelegramController::class, 'getUpdates'])
                ->name('telegram/getUpdates'),

            // No auth middleware: Telegram's servers must be able to POST here.
            // Secured by X-Telegram-Bot-Api-Secret-Token header verification only.
            Route::methods([Method::GET, Method::POST], '/telegram/webhook')
                ->action([TelegramController::class, 'webhook'])
                ->name('telegram/webhook'),

            Route::get('/telegram/send-invoice/{inv_id:\d+}')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([TelegramController::class, 'sendInvoice'])
                ->name('telegram/sendInvoice'),

            Route::get('/telegram/invoice-link/{inv_id:\d+}')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([TelegramController::class, 'createInvoiceLink'])
                ->name('telegram/invoiceLink'),

            Route::get('/telegram/send-pdf/{inv_id:\d+}')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([TelegramController::class, 'sendPdf'])
                ->name('telegram/sendPdf'),

            Route::get('/telegram/send-location')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([TelegramController::class, 'sendLocation'])
                ->name('telegram/sendLocation'),

            Route::get('/telegram/refund-stars/{payment_id:\d+}')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([TelegramController::class, 'refundStars'])
                ->name('telegram/refundStars'),
        ), // invoice
];
