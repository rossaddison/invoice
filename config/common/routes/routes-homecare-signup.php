<?php

declare(strict_types=1);

use App\Auth\Controller\HomeCareSignupController;
use App\Middleware\RateLimiter;
use Yiisoft\Http\Method;
use Yiisoft\Router\Route;

$mG = Method::GET;
$mP = Method::POST;

return [
    // Public HomeCare-specific signup form. Separate from the generic
    // /signup — always creates and assigns a Client, resolves/creates the
    // street (Family) and house-number (Product, Service-type), and raises
    // the first invoice on confirmation-link click. See
    // src/Auth/Controller/HomeCareSignupController.php.
    Route::methods([$mG, $mP], '/homecare-signup')
        ->middleware(RateLimiter::global(50, 10))
        ->middleware(RateLimiter::perIp(5, 'homecare_signup'))
        ->action([HomeCareSignupController::class, 'signup'])
        ->name('homecare/signup'),
    // Not under RoutePermission::invoiceGroup(): a brand-new signup clicking
    // this emailed link has no app session yet. Secured by the masked,
    // time-limited token in the URL itself, not RBAC — mirrors
    // userinv/signup in config/common/routes/routes-user-inv.php.
    Route::methods([$mG, $mP], '/homecare-signup/confirm/{language}/{token}/{tokenType}')
        ->name('homecare/signup-confirm')
        ->action([HomeCareSignupController::class, 'confirm']),
];
