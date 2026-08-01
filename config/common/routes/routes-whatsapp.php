<?php

declare(strict_types=1);

use App\Auth\Permissions;
use App\Invoice\WhatsApp\WhatsAppController;
use App\Middleware\RoutePermission;
use Yiisoft\Http\Method;
use Yiisoft\Router\Route;

return [
    RoutePermission::invoiceGroup(

            Route::get('/whatsapp')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([WhatsAppController::class, 'index'])
                ->name('whatsapp/index'),
        ), // invoice

    // Not under RoutePermission::invoiceGroup(): Meta's servers must be able
    // to reach this with no app session, both for the GET verification
    // handshake and any future POST event delivery. Secured by
    // whatsapp_webhook_verify_token on the GET handshake only — the group's
    // Authentication middleware would otherwise reject every call before
    // that check ever runs. Mirrors routes-telegram.php's webhook route.
    Route::methods([Method::GET, Method::POST], '/whatsapp/webhook')
        ->action([WhatsAppController::class, 'webhook'])
        ->name('whatsapp/webhook'),
];
