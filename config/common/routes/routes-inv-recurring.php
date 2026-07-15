<?php

declare(strict_types=1);

use App\Auth\Permissions;
use App\Invoice\InvRecurring\InvRecurringController;
use App\Middleware\RoutePermission;
use Yiisoft\Http\Method;
use Yiisoft\Router\Route;

return [
    RoutePermission::invoiceGroup(

            // InvRecurring
            Route::get('/invrecurring')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([InvRecurringController::class, 'index'])
                ->name('invrecurring/index'),

            // Add
            Route::methods([Method::GET, Method::POST], '/invrecurring/add/{inv_id}')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([InvRecurringController::class, 'add'])
                ->name('invrecurring/add'),

            // Create via inv.js create_recurring_confirm
            Route::methods([Method::GET, Method::POST], '/invrecurring/multiple')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([InvRecurringController::class, 'multiple'])
                ->name('invrecurring/multiple'),

            Route::methods([Method::GET, Method::POST], '/invrecurring/getRecurStartDate')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([InvRecurringController::class, 'getRecurStartDate'])
                ->name('invrecurring/getRecurStartDate'),

            // Edit
            Route::methods([Method::GET, Method::POST], '/invrecurring/start/{id}')
                ->name('invrecurring/start')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([InvRecurringController::class, 'start']),

            Route::methods([Method::GET, Method::POST], '/invrecurring/delete/{id}')
                ->name('invrecurring/delete')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([InvRecurringController::class, 'delete']),

            Route::methods([Method::GET, Method::POST], '/invrecurring/stop/{id}')
                ->name('invrecurring/stop')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([InvRecurringController::class, 'stop']),

            Route::methods([Method::GET, Method::POST], '/invrecurring/view/{id}')
                ->name('invrecurring/view')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([InvRecurringController::class, 'view']),

            Route::methods([Method::GET, Method::POST], '/invrecurring/create-from-productclient/{client_id}')
                ->name('invrecurring/create-from-productclient')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([InvRecurringController::class, 'createFromProductClient']),
        ), // invoice

    // Not under RoutePermission::invoiceGroup(): called by an external
    // scheduled cron job with no app session. Secured by its own cron_key
    // query parameter matched against the stored setting, not RBAC — the
    // group's Authentication middleware would otherwise reject every call
    // before that check ever runs.
    Route::get('/invrecurring/cron')
        ->action([InvRecurringController::class, 'cron'])
        ->name('invrecurring/cron'),
];
