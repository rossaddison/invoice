<?php

declare(strict_types=1);

use App\Invoice\Peppol\PeppolInboundController;
use Yiisoft\Http\Method;
use Yiisoft\Router\Route;

return [
    // Not under RoutePermission::invoiceGroup(): the Oxalis AS4 access
    // point calls this directly with no app session. Secured at the
    // Peppol/AS4 transport layer, not RBAC — the group's Authentication
    // middleware would otherwise reject every call before it's ever
    // processed.
    Route::methods([Method::GET, Method::POST], '/peppol/inbound/delivery')
        ->action([PeppolInboundController::class, 'delivery'])
        ->name('peppol/inbound/delivery'),
];
