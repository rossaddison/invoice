<?php

declare(strict_types=1);

use App\Auth\Permissions;
use App\Invoice\Quote\QuoteController;
use App\Middleware\RoutePermission;
use Yiisoft\Http\Method;
use Yiisoft\Router\Route;

return [
    RoutePermission::invoiceGroup(
Route::get('/client_quotes[/page/{page:\d+}[/status/{status:\d+}]]')
                ->middleware(RoutePermission::check(Permissions::VIEW_INV))
                ->action([QuoteController::class, 'guest'])
                ->name('quote/guest'),

            Route::methods([Method::GET, Method::POST], '/quote/pdf/{include}')
                ->middleware(RoutePermission::check(Permissions::VIEW_INV))
                ->action([QuoteController::class, 'pdf'])
                ->name('quote/pdf'),

            Route::methods([Method::GET, Method::POST], '/quote/quoteToSoConfirm')
                ->middleware(RoutePermission::check(Permissions::VIEW_INV))
                ->action([QuoteController::class, 'quoteToSoConfirm'])
                ->name('quote/quoteToSoConfirm'),

            Route::methods([Method::GET, Method::POST], '/quote/view/{id}')
                ->name('quote/view')
                ->middleware(RoutePermission::check(Permissions::VIEW_INV))
                ->action([QuoteController::class, 'view']),

// The individual must have been give the url on the email sent and also
// have been assigned the observer role under resources/rbac/items by using
// assignRole command at command prompt
            Route::methods([Method::GET, Method::POST], '/quote/urlKey/{url_key}')
                ->name('quote/urlKey')
                ->middleware(RoutePermission::check(Permissions::VIEW_INV))
                ->action([QuoteController::class, 'urlKey']),

// The individual that is sent the quote approves with/without a purchase
// order number
            Route::methods([Method::GET, Method::POST], '/quote/approve')
                ->name('quote/approve')
                ->middleware(RoutePermission::check(Permissions::VIEW_INV))
                ->action([QuoteController::class, 'approve']),

// The individual that is sent the quote rejects it.
            Route::methods([Method::GET, Method::POST], '/quote/reject/{url_key}')
                ->name('quote/reject')
                ->middleware(RoutePermission::check(Permissions::VIEW_INV))
                ->action([QuoteController::class, 'reject']),

            Route::methods([Method::GET, Method::POST], '/quote/add/{origin}')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([QuoteController::class, 'add'])
                ->name('quote/add'),

            Route::methods([Method::GET, Method::POST], '/quote/emailStage0/{id}')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([QuoteController::class, 'emailStage0'])
                ->name('quote/emailStage0'),

            Route::methods([Method::GET, Method::POST], '/quote/emailStage2/{id}')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([QuoteController::class, 'emailStage2'])
                ->name('quote/emailStage2'),

            Route::get('/quote[/page/{page:\d+}[/status/{status:\d+}]]')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([QuoteController::class, 'index'])
                ->name('quote/index'),

            Route::methods([Method::GET, Method::POST], '/quote/saveCustom')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([QuoteController::class, 'saveCustom'])
                ->name('quote/saveCustom'),

            Route::methods([Method::GET, Method::POST], '/quote/saveQuoteTaxRate')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([QuoteController::class, 'saveQuoteTaxRate'])
                ->name('quote/saveQuoteTaxRate'),

            Route::methods([Method::GET, Method::POST], '/quote/changeStatus')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([QuoteController::class, 'changeStatus'])
                ->name('quote/changeStatus'),

            Route::methods([Method::GET, Method::POST], '/quote/deleteQuoteTaxRate/{id}')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([QuoteController::class, 'deleteQuoteTaxRate'])
                ->name('quote/deleteQuoteTaxRate'),

            Route::methods([Method::GET, Method::POST], '/quote/deleteQuoteItem/{id}')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([QuoteController::class, 'deleteQuoteItem'])
                ->name('quote/deleteQuoteItem'),

            Route::methods([Method::GET, Method::POST], '/quote/pdfDashboardIncludeCf/{id}')
                ->middleware(RoutePermission::check(Permissions::VIEW_INV))
                ->action([QuoteController::class, 'pdfDashboardIncludeCf'])
                ->name('quote/pdfDashboardIncludeCf'),

            Route::methods([Method::GET, Method::POST], '/quote/pdfDashboardExcludeCf/{id}')
                ->middleware(RoutePermission::check(Permissions::VIEW_INV))
                ->action([QuoteController::class, 'pdfDashboardExcludeCf'])
                ->name('quote/pdfDashboardExcludeCf'),

            Route::methods([Method::GET, Method::POST], '/quote/modalcreate')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([QuoteController::class, 'modalcreate'])
                ->name('quote/modalcreate'),

            Route::methods([Method::GET, Method::POST], '/quote/confirm')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([QuoteController::class, 'confirm'])
                ->name('quote/confirm'),

            Route::methods([Method::GET, Method::POST], '/quote/createConfirm')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([QuoteController::class, 'createConfirm'])
                ->name('quote/createConfirm'),

            Route::methods([Method::GET, Method::POST], '/quote/quoteToInvoiceConfirm')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([QuoteController::class, 'quoteToInvoiceConfirm'])
                ->name('quote/quoteToInvoiceConfirm'),

            Route::methods([Method::GET, Method::POST], '/quote/quoteToQuoteConfirm')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([QuoteController::class, 'quoteToQuoteConfirm'])
                ->name('quote/quoteToQuoteConfirm'),

            Route::methods([Method::GET, Method::POST], '/quote/modalChangeClient')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([QuoteController::class, 'modalChangeClient'])
                ->name('quote/modalChangeClient'),

            Route::methods([Method::GET, Method::POST], '/quote/edit/{id}')
                ->name('quote/edit')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([QuoteController::class, 'edit']),

            Route::methods([Method::GET, Method::POST], '/quote/test')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([QuoteController::class, 'test'])
                ->name('quote/test'),

            Route::methods([Method::GET, Method::POST], '/quote/save')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([QuoteController::class, 'save'])
                ->name('quote/save'),

            Route::methods([Method::GET, Method::POST], '/quote/delete/{id}')
                ->name('quote/delete')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([QuoteController::class, 'delete']),

            Route::methods([Method::GET, Method::POST], '/quote/generateQuotePdf/{url_key}')
                ->name('quote/generateQuotePdf')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([QuoteController::class, 'generateQuotePdf']),
        ), // invoice
];
