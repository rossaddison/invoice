<?php

declare(strict_types=1);

use App\Invoice\As4\As4ReceiveController;
use Yiisoft\Http\Method;
use Yiisoft\Router\Route;

return [
    // Not under RoutePermission::invoiceGroup(): bilateral AS4 trading
    // partners POST directly here with no app session. Secured by
    // AS4/ebMS3 message signing, not RBAC — the group's Authentication
    // middleware would otherwise reject every call before the message is
    // ever parsed.
    Route::methods([Method::POST], '/as4/receive')
        ->action([As4ReceiveController::class, 'receive'])
        ->name('as4/receive'),
];
