<?php

declare(strict_types=1);

use App\Auth\Permissions;
use App\Invoice\InvRecurring\InvRecurringController;
use App\Middleware\RoutePermission;
use Yiisoft\Auth\Middleware\Authentication;
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
    // scheduled cron job with no app session, so session-based RBAC
    // (RoutePermission::check()) doesn't apply here at all — there's no
    // logged-in user to check a permission against. This is the one route
    // in the app where Yiisoft\Auth\Middleware\Authentication is actually
    // the right tool: a program, not a person, making a single standalone
    // request. See config/web/di/cron-auth.php for the AuthenticatorInterface
    // binding this middleware needs to be constructible at all, and
    // docs/INVRECURRING_CRON_BEARER_AUTH_AUGUST_2026.md for the full
    // before/after story (this route used to check a `cron_key` query
    // parameter by hand, with no real HTTP semantics on failure).
    Route::get('/invrecurring/cron')
        ->middleware(Authentication::class)
        ->action([InvRecurringController::class, 'cron'])
        ->name('invrecurring/cron'),
];
