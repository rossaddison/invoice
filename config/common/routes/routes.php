<?php

declare(strict_types=1);

use App\Auth\Controller\{
    AuthController, ChangePasswordController, ForgotPasswordController,
    ResetPasswordController, SignupController};
use App\Auth\Permissions;
use App\Contact\ContactController;
use App\Controller\{Actions\ApiInfo, SiteAuthController, SiteController};
use App\Invoice\{
    Inv\InvController,
    Prometheus\PrometheusController,
};
use App\Middleware\{ApiDataWrapper, RateLimiter, RoutePermission};
use App\User\Controller\{ApiUserController, UserController};
use Yiisoft\{
    DataResponse\ResponseFactory\DataResponseFactoryInterface as DRFI,
    DataResponse\Middleware\JsonDataResponseMiddleware,
    DataResponse\Middleware\XmlDataResponseMiddleware,
    Http\Method, Router\Group, Router\Route, Yii\AuthClient\AuthAction,
    Yii\RateLimiter\LimitRequestsMiddleware as LRM};

$mG = Method::GET;
$mP = Method::POST;

/**
 * Note: If middleware is used, it must always be inserted before the action
 */

return [
    // Prometheus monitoring endpoints
    // This endpoint needs to be publicly accessible for Prometheus server
    // to scrape it. No authentication middleware needed.
    Route::get('/metrics')
        ->action([PrometheusController::class, 'metrics'])
        ->name('prometheus/metrics'),
    // Health check endpoint should be accessible for monitoring systems.
    Route::get('/prometheus/health')
        ->action([PrometheusController::class, 'health'])
        ->name('prometheus/health'),
    // Public homecare-cleaning QR scan endpoint. Deliberate bearer-token
    // exception to this app's usual guest-access model, where a url_key
    // alone never grants access without an authenticated session — here,
    // knowing the printed token is sufficient. No Authentication middleware
    // by design; see the homecare-cleaning QR auto-invoice implementation plan.
    Route::get('/scan/{token}')
        // Outer: 60 total scans per 60 s across all clients/tokens
        ->middleware(RateLimiter::global(60))
        // Inner: 10 per 60 s per real IP via CF-Connecting-IP
        ->middleware(RateLimiter::perIp(10, 'homecare_scan_route'))
        ->action([InvController::class, 'homecareScan'])
        ->name('public/homecare-scan'),
    // Admin dashboard that can use the existing authentication middleware
    // if needed.
    Route::get('/prometheus/dashboard')
        ->middleware(RoutePermission::check(Permissions::EDIT_INV))
        ->action([PrometheusController::class, 'dashboard'])
        ->name('prometheus/dashboard'),

    // Lonely pages of site
    Route::get('/')
        ->action([SiteController::class, 'index'])
        ->name('site/index'),
    Route::methods([$mG, $mP], '/interest')
        ->action([ContactController::class, 'interest'])
        ->name('contact/interest'),
    Route::methods([$mG, $mP], '/about')
        ->action([SiteController::class, 'about'])
        ->name('site/about'),
    Route::methods([$mG, $mP], '/accreditations')
        ->action([SiteController::class, 'accreditations'])
        ->name('site/accreditations'),
    Route::methods([$mG, $mP], '/oauth2autherror/{message}')
        ->action([SiteController::class, 'oauth2autherror'])
        ->name('site/oauth2autherror'),
    Route::methods([$mG, $mP], '/adminmustmakeactive')
        ->action([SiteController::class, 'adminmustmakeactive'])
        ->name('site/adminmustmakeactive'),
    Route::methods([$mG, $mP], '/emailnotverified')
        ->action([SiteController::class, 'emailnotverified'])
        ->name('site/emailnotverified'),
    Route::methods([$mG, $mP], '/team')
        ->action([SiteController::class, 'team'])
        ->name('site/team'),
    Route::methods([$mG, $mP], '/pricing')
        ->action([SiteController::class, 'pricing'])
        ->name('site/pricing'),
    Route::methods([$mG, $mP], '/testimonial')
        ->action([SiteController::class, 'testimonial'])
        ->name('site/testimonial'),
    Route::methods([$mG, $mP], '/contact')
        ->action([SiteController::class, 'contact'])
        ->name('site/contact'),
    Route::methods([$mG, $mP], '/gallery')
        ->action([SiteController::class, 'gallery'])
        ->name('site/gallery'),
    Route::methods([$mG, $mP], '/oauth2callbackresultunauthorised')
        ->action([SiteController::class, 'oauth2callbackresultunauthorised'])
        ->name('site/oauth2callbackresultunauthorised'),
    Route::methods([$mG, $mP], '/usercancelledoauth2')
        ->action([SiteController::class, 'usercancelledoauth2'])
        ->name('site/usercancelledoauth2'),
    Route::methods([$mG, $mP], '/forgotalert')
        ->action([SiteAuthController::class, 'forgotalert'])
        ->name('site/forgotalert'),
    Route::methods([$mG, $mP], '/forgotemailfailed')
        ->action([SiteAuthController::class, 'forgotemailfailed'])
        ->name('site/forgotemailfailed'),
    Route::methods([$mG, $mP], '/forgotusernotfound')
        ->action([SiteAuthController::class, 'forgotusernotfound'])
        ->name('site/forgotusernotfound'),
    Route::methods([$mG, $mP], '/onetimepassworderror')
        ->action([SiteAuthController::class, 'onetimepassworderror'])
        ->name('site/onetimepassworderror'),
    Route::methods([$mG, $mP], '/onetimepasswordfailure')
        ->action([SiteAuthController::class, 'onetimepasswordfailure'])
        ->name('site/onetimepasswordfailure'),
    Route::methods([$mG, $mP], '/onetimepasswordsuccess')
        ->action([SiteAuthController::class, 'onetimepasswordsuccess'])
        ->name('site/onetimepasswordsuccess'),
    Route::methods([$mG, $mP], '/privacypolicy')
        ->action([SiteController::class, 'privacypolicy'])
        ->name('site/privacypolicy'),
    Route::methods([$mG, $mP], '/resetpasswordfailed')
        ->action([SiteAuthController::class, 'resetpasswordfailed'])
        ->name('site/resetpasswordfailed'),
    Route::methods([$mG, $mP], '/resetpasswordsuccess')
        ->action([SiteAuthController::class, 'resetpasswordsuccess'])
        ->name('site/resetpasswordsuccess'),
    Route::methods([$mG, $mP], '/signupfailed')
        ->action([SiteAuthController::class, 'signupfailed'])
        ->name('site/signupfailed'),
    Route::methods([$mG, $mP], '/signupsuccess')
        ->action([SiteAuthController::class, 'signupsuccess'])
        ->name('site/signupsuccess'),
    Route::methods([$mG, $mP], '/termsofservice')
        ->action([SiteController::class, 'termsofservice'])
        ->name('site/termsofservice'),
    // Auth
    Route::methods([$mG, $mP], '/login')
        // Outer: 30 total POSTs per 60 s on /login, regardless of IP
        ->middleware(RateLimiter::global(30))
        // Inner: 5 per 60 s per real IP via CF-Connecting-IP
        ->middleware(RateLimiter::perIp(5, 'login_route'))
        ->action([AuthController::class, 'login'])
        ->name('auth/login'),
    Route::get('/authclient')
        ->action([AuthController::class, 'authclient'])
        ->name('auth/authclient'),
    Route::methods([$mG, $mP], '/callback')
        ->middleware(LRM::class)
        ->middleware(AuthAction::class)
        ->action([AuthController::class, 'callback'])
        ->name('auth/callback'),
    Route::methods([$mG, $mP], '/callbackDeveloperGovSandboxHmrc')
        ->middleware(LRM::class)
        ->action([AuthController::class, 'callbackDeveloperGovSandboxHmrc'])
        ->name('auth/callbackDeveloperGovSandboxHmrc'),
    /*
     * token e.g. maskedToken, tokenType e.g. email-verification, facebook-access,
     * github-access
     * Related logic: see AuthController function getTokenType($provider)
     */
    Route::methods([$mG, $mP], '/callbackFacebook')
        ->middleware(LRM::class)
        ->action([AuthController::class, 'callbackFacebook'])
        ->name('auth/callbackFacebook'),
    Route::methods([$mG, $mP], '/callbackGithub')
        ->middleware(LRM::class)
        ->action([AuthController::class, 'callbackGithub'])
        ->name('auth/callbackGithub'),
    Route::methods([$mG, $mP], '/callbackGoogle')
        ->middleware(LRM::class)
        ->action([AuthController::class, 'callbackGoogle'])
        ->name('auth/callbackGoogle'),
    Route::methods([$mG, $mP], '/callbackGovUk')
        ->middleware(LRM::class)
        ->action([AuthController::class, 'callbackGovUk'])
        ->name('auth/callbackGovUk'),
    Route::methods([$mG, $mP], '/callbackLinkedIn')
        ->middleware(LRM::class)
        ->action([AuthController::class, 'callbackLinkedIn'])
        ->name('auth/callbackLinkedIn'),
    Route::methods([$mG, $mP], '/callbackMicrosoftOnline')
        ->middleware(LRM::class)
        ->action([AuthController::class, 'callbackMicrosoftOnline'])
        ->name('auth/callbackMicrosoftOnline'),
    Route::methods([$mG, $mP], '/callbackVKontakte')
        ->middleware(LRM::class)
        ->action([AuthController::class, 'callbackVKontakte'])
        ->name('auth/callbackVKontakte'),
    Route::methods([$mG, $mP], '/callbackX')
        ->middleware(LRM::class)
        ->action([AuthController::class, 'callbackX'])
        ->name('auth/callbackX'),
    Route::methods([$mG, $mP], '/callbackYandex')
        ->middleware(LRM::class)
        ->action([AuthController::class, 'callbackYandex'])
        ->name('auth/callbackYandex'),
    Route::post('/logout')
        ->action([AuthController::class, 'logout'])
        ->name('auth/logout'),
    Route::methods([$mG, $mP], '/showSetup')
        ->middleware(LRM::class)
        ->action([AuthController::class, 'showSetup'])
        ->name('auth/showSetup'),
    Route::methods([$mG, $mP], '/ajaxShowSetup')
        ->middleware(LRM::class)
        ->action([AuthController::class, 'ajaxShowSetup'])
        ->name('auth/ajaxShowSetup'),
    Route::methods([$mG, $mP], '/verifySetup')
        ->action([AuthController::class, 'verifySetup'])
        ->name('auth/verifySetup'),
    Route::methods([$mG, $mP], '/verifyLogin')
        ->action([AuthController::class, 'verifyLogin'])
        ->name('auth/verifyLogin'),
    Route::methods([$mG, $mP], '/regenerateCodes')
        ->middleware(RoutePermission::check(Permissions::VIEW_INV))
        ->action([AuthController::class, 'regenerateCodes'])
        ->name('auth/regenerateCodes'),
    Route::methods([$mG, $mP], '/forgotpassword')
        // Global path counter — 5 POSTs per 60 s; triggers email so kept tight
        ->middleware(RateLimiter::global(5))
        // Per real-IP via CF-Connecting-IP; CAS fail → 429
        ->middleware(RateLimiter::perIp(2, 'forgot_route'))
        ->action([ForgotPasswordController::class, 'forgot'])
        ->name('auth/forgotpassword'),
    Route::methods([$mG, $mP],
            '/resetpassword/resetpassword/{token}')
        // Global path counter — 10 POSTs per 60 s; token gate makes this low-traffic
        ->middleware(RateLimiter::global(10))
        // Per real-IP via CF-Connecting-IP; CAS fail → 429
        ->middleware(RateLimiter::perIp(3, 'reset_route'))
        ->action([ResetPasswordController::class, 'resetpassword'])
        ->name('auth/resetpassword'),
    // email-verification token is masked before sending by email
    // and must be unmasked after inbox click. Refer to userinv/signup
    Route::methods([$mG, $mP], '/signup')
        // Fix #2: global path counter — blocks botnet waves regardless of IP
        ->middleware(RateLimiter::global(50, 10))
        // Fix #1 & #3: per real-IP via CF-Connecting-IP; CAS fail → 429
        ->middleware(RateLimiter::perIp(5, 'signup'))
        ->action([SignupController::class, 'signup'])
        ->name('auth/signup'),
    Route::methods([$mG, $mP], '/change')
        // Global path counter — 10 POSTs per 60 s regardless of IP
        ->middleware(RateLimiter::global(10))
        // Per real-IP via CF-Connecting-IP; CAS fail → 429
        ->middleware(RateLimiter::perIp(3, 'change_route'))
        ->action([ChangePasswordController::class, 'change'])
        ->name('auth/change'),
    Group::create('/user')
        ->routes(
            // User
            Route::methods(['GET', 'POST'], '[/{page:\d+}]')
                ->name('user/index')
                ->action([UserController::class, 'index']),
            // Profile page
            Route::get('/{login}')
                ->action([UserController::class, 'profile'])
                ->name('user/profile'),
        ),
    Group::create('/api')
        ->middleware(XmlDataResponseMiddleware::class)
        ->middleware(ApiDataWrapper::class)
        ->routes(
            Route::get('/info/v1')
                ->name('api/info/v1')
                ->action(function (DRFI $responseFactory) {
                    return $responseFactory->createResponse(
                            ['version' => '1.0', 'author' => 'yiisoft']);
                }),
            Route::get('/info/v2')
                ->name('api/info/v2')
                ->middleware(JsonDataResponseMiddleware::class)
                ->action(ApiInfo::class),
            Route::get('/user')
                ->name('api/user/index')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([ApiUserController::class, 'index']),
            Route::get('/user/{login}')
                ->name('api/user/profile')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->middleware(JsonDataResponseMiddleware::class)
                ->action([ApiUserController::class, 'profile']),
        ),
];
