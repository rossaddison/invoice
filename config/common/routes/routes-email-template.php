<?php

declare(strict_types=1);

use App\Auth\Permissions;
use App\Invoice\EmailTemplate\EmailTemplateController;
use App\Middleware\RoutePermission;
use Yiisoft\Http\Method;
use Yiisoft\Router\Route;

return [
    RoutePermission::invoiceGroup(

            Route::get('/emailtemplate')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([EmailTemplateController::class, 'index'])
                ->name('emailtemplate/index'),

            Route::methods([Method::GET, Method::POST], '/emailtemplate/addInvoice')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([EmailTemplateController::class, 'addInvoice'])
                ->name('emailtemplate/addInvoice'),

            Route::methods([Method::GET, Method::POST],
                    '/emailtemplate/editInvoice/{email_template_id}')
                ->name('emailtemplate/editInvoice')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([EmailTemplateController::class, 'editInvoice']),

            Route::methods([Method::GET, Method::POST], '/emailtemplate/addQuote')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([EmailTemplateController::class, 'addQuote'])
                ->name('emailtemplate/addQuote'),

            Route::methods([Method::GET, Method::POST],
                    '/emailtemplate/editQuote/{email_template_id}')
                ->name('emailtemplate/editQuote')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([EmailTemplateController::class, 'editQuote']),

            Route::methods([Method::GET, Method::POST],
                    '/emailtemplate/delete/{email_template_id}')
                ->name('emailtemplate/delete')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([EmailTemplateController::class, 'delete']),

            Route::methods([Method::GET, Method::POST],
                    '/emailtemplate/getContent/{email_template_id}')
                ->name('emailtemplate/getContent')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([EmailTemplateController::class, 'getContent']),

            Route::get('/emailtemplate/bodyPreview')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([EmailTemplateController::class, 'bodyPreview'])
                ->name('emailtemplate/bodyPreview'),

            Route::methods([Method::GET, Method::POST],
                    '/emailtemplate/preview/{email_template_id}')
                ->name('emailtemplate/preview')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([EmailTemplateController::class, 'preview']),

            Route::methods([Method::GET, Method::POST], '/emailtemplate/view/{email_template_id}')
                ->name('emailtemplate/view')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([EmailTemplateController::class, 'view']),
        ), // invoice
];
