<?php

declare(strict_types=1);

use App\Auth\Permissions;
use App\Invoice\Family\FamilyController;
use App\Middleware\RoutePermission;
use Yiisoft\DataResponse\Middleware\JsonDataResponseMiddleware;
use Yiisoft\Http\Method;
use Yiisoft\Router\Route;

return [
    RoutePermission::invoiceGroup(
        Route::get('/family[/page/{page:\d+}]')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([FamilyController::class, 'index'])
                ->name('family/index'),
        Route::methods([Method::GET, Method::POST], '/family/test')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->middleware(JsonDataResponseMiddleware::class)
                ->action([FamilyController::class])
                ->name('family/test'),
        Route::methods([Method::GET, Method::POST], '/family/add')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([FamilyController::class, 'add'])
                ->name('family/add'),
        Route::methods([Method::GET, Method::POST], '/family/edit/{id}')
                ->name('family/edit')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([FamilyController::class, 'edit']),
        Route::methods([Method::GET, Method::POST], '/family/delete/{id}')
                ->name('family/delete')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([FamilyController::class, 'delete']),

        // Dependency Dropdown form Load _search form
        Route::methods([Method::GET, Method::POST], '/family/search')
            ->name('family/search')
            ->middleware(RoutePermission::check(Permissions::EDIT_INV))
            ->action([FamilyController::class, 'search']),

        // Dependency Dropdown form Load CategorySecondary items after
        // category_primary_id selection
        Route::methods(
            [Method::GET, Method::POST],
            '/family/secondaries/{category_primary_id}'
        )
            ->name('family/secondaries')
            ->middleware(RoutePermission::check(Permissions::EDIT_INV))
            ->action([FamilyController::class, 'secondaries']),

        // Dependency Dropdown form Load Family Names
        Route::methods([Method::GET, Method::POST], '/family/names/{category_secondary_id}')
            ->name('family/names')
            ->middleware(RoutePermission::check(Permissions::EDIT_INV))
            ->action([FamilyController::class, 'names']),

        // Dependency Dropdown form Load Products associated with family id
        Route::methods([Method::GET, Method::POST], '/family/products/{id}')
            ->name('family/products')
            ->middleware(RoutePermission::check(Permissions::EDIT_INV))
            ->action([FamilyController::class, 'products']),
        Route::methods([Method::GET, Method::POST], '/family/view/{id}')
                ->name('family/view')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([FamilyController::class, 'view']),

        // Generate products from selected families
        Route::methods([Method::GET, Method::POST], '/family/generateProducts')
            ->name('family/generateProducts')
            ->middleware(RoutePermission::check(Permissions::EDIT_INV))
            ->action([FamilyController::class, 'generateProducts']),

        // Cleaning run: display drag-and-drop street order page
        Route::get('/family/street-order')
            ->name('family/streetOrder')
            ->middleware(RoutePermission::check(Permissions::EDIT_INV))
            ->action([FamilyController::class, 'streetOrder']),

        // Cleaning run: persist new street order (called by TypeScript drag-and-drop)
        Route::post('/family/reorder')
            ->name('family/reorder')
            ->middleware(RoutePermission::check(Permissions::EDIT_INV))
            ->middleware(JsonDataResponseMiddleware::class)
            ->action([FamilyController::class, 'reorder']),
    ), // invoice
];
