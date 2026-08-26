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

// One sweep across every locale that already has its own
// resources/messages/{locale}/app.php: diffs each against en/app.php,
// translates only the missing keys, merges + sorts + writes the file
// back in place. Replaces the manual output_overwrite -> copy -> merge
// -> sort cycle the 'diff' route above still requires one locale at a
// time. Ignores the google_translate_locale Setting entirely -- every
// locale directory's own name is used as the Google target language.
            Route::methods([Method::GET, Method::POST], '/generator/googleTranslateAllLocalesDiff')
                ->name('generator/googleTranslateAllLocalesDiff')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([GeneratorGoogleTranslateController::class, 'googleTranslateAllLocalesDiff']),
        ), // invoice
];
