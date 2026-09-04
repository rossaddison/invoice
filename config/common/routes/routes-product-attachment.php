<?php

declare(strict_types=1);

use App\Auth\Permissions;
use App\Invoice\Product\ProductAttachmentController;
use App\Middleware\RoutePermission;
use Yiisoft\Http\Method;
use Yiisoft\Router\Route;

return [
    RoutePermission::invoiceGroup(
        Route::methods([Method::GET, Method::POST], '/image/{product_image_id}')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([ProductAttachmentController::class, 'downloadImageFile'])
                ->name('product/downloadImageFile'),
        Route::methods([Method::GET, Method::POST], '/image_attachment/{id}')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([ProductAttachmentController::class, 'imageAttachment'])
                ->name('product/imageAttachment'),
    ), // invoice
];
