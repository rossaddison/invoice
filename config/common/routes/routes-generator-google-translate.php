<?php

declare(strict_types=1);

use App\Auth\Permissions;
use App\Invoice\Generator\GeneratorGoogleTranslateController;
use App\Middleware\RoutePermission;
use Yiisoft\Http\Method;
use Yiisoft\Router\Route;

return [
    RoutePermission::invoiceGroup(

// type = eg. 'app', or 'diff'
// Translate either app_lang, diff_lang.php in src/Invoice/Language/English
// using Setting google_translate_locale under Settings...
// View...Google Translate
            Route::methods([Method::GET, Method::POST], '/generator/googleTranslateLang/{type}')
                ->name('generator/googleTranslateLang')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([GeneratorGoogleTranslateController::class, 'googleTranslateLang']),

// Translate info documentation files like invoice.php
// from resources/views/invoice/info/en/invoice.php to target language folder
            Route::methods([Method::GET, Method::POST], '/generator/googleTranslateInfo')
                ->name('generator/googleTranslateInfo')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([GeneratorGoogleTranslateController::class, 'googleTranslateInfo']),
        ), // invoice
];
