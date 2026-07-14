<?php

declare(strict_types=1);

use App\Invoice\As4\As4ReceiveController;
use App\Middleware\RoutePermission;
use Yiisoft\Http\Method;
use Yiisoft\Router\Route;

return [
    RoutePermission::invoiceGroup(

            // No auth middleware: bilateral AS4 trading partners POST directly here.
            Route::methods([Method::POST], '/as4/receive')
                ->action([As4ReceiveController::class, 'receive'])
                ->name('as4/receive'),
        ), // invoice
];
