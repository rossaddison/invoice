<?php

declare(strict_types=1);

use App\Auth\Controller\{
    AuthController, ChangePasswordController, ForgotPasswordController,
    ResetPasswordController, SignupController};
use App\Auth\Permissions;
use App\Contact\ContactController;
use App\Controller\{Actions\ApiInfo, SiteController};
use App\Invoice\{
    AllowanceCharge\AllowanceChargeController,
    CategoryPrimary\CategoryPrimaryController,
    CategorySecondary\CategorySecondaryController,
    Client\ClientController,
    ClientNote\ClientNoteController,
    ClientPeppol\ClientPeppolController,
    Company\CompanyController,
    CompanyPrivate\CompanyPrivateController,
    Contract\ContractController,
    CustomField\CustomFieldController,
    CustomValue\CustomValueController,
    Delivery\DeliveryController,
    DeliveryLocation\DeliveryLocationController,
    DeliveryParty\DeliveryPartyController,
    EmailTemplate\EmailTemplateController,
    Family\FamilyController,
    FromDropDown\FromDropDownController,
    Generator\GeneratorController,
    GeneratorRelation\GeneratorRelationController,
    Group\GroupController,
    Import\ImportController,
    Inv\InvController,
    InvAllowanceCharge\InvAllowanceChargeController,
    InvItem\InvItemController,
    InvItemAllowanceCharge\InvItemAllowanceChargeController,
    InvoiceController as ICLR,
    InvRecurring\InvRecurringController,
    InvSentLog\InvSentLogController,
    ItemLookup\ItemLookupController,
    Merchant\MerchantController,
    Payment\PaymentController,
    PaymentInformation\PaymentInformationController as PICLR,
    PaymentMethod\PaymentMethodController,
    PaymentPeppol\PaymentPeppolController,
    PostalAddress\PostalAddressController,
    Product\ProductController,
    ProductClient\ProductClientController,
    ProductImage\ProductImageController,
    ProductProperty\ProductPropertyController,
    Prometheus\PrometheusController,
    Profile\ProfileController,
    Project\ProjectController,
    Quote\QuoteController,
    QuoteAllowanceCharge\QuoteAllowanceChargeController,
    QuoteItem\QuoteItemController,
    QuoteItemAllowanceCharge\QuoteItemAllowanceChargeController,
    Report\ReportController,
    SalesOrder\SalesOrderController,
    SalesOrderItem\SalesOrderItemController,
    Setting\SettingController,
    Task\TaskController,
    TaxRate\TaxRateController,
    Telegram\TelegramController,
    Unit\UnitController,
    UnitPeppol\UnitPeppolController,
    Upload\UploadController,
    UserClient\UserClientController,
    // Quote
    UserInv\UserInvController};
use App\Middleware\{AccessChecker as AC, ApiDataWrapper};
use App\User\Controller\{ApiUserController, UserController};
use Psr\Http\Message\ResponseFactoryInterface;
use Yiisoft\{Auth\Middleware\Authentication,
    DataResponse\DataResponseFactoryInterface as DRFI,
    DataResponse\Middleware\FormatDataResponseAsJson,
    DataResponse\Middleware\FormatDataResponseAsXml,
    Http\Method, Router\Group, Router\Route, Yii\AuthClient\AuthAction,
    Yii\RateLimiter\Counter, Yii\RateLimiter\LimitRequestsMiddleware as LRM,
    Yii\RateLimiter\Storage\StorageInterface};

$pEI = Permissions::EDIT_INV;
$pVI = Permissions::VIEW_INV;
$pEP = Permissions::EDIT_PAYMENT;
$pVP = Permissions::VIEW_PAYMENT;
$pECP = Permissions::EDIT_CLIENT_PEPPOL;
$pEUI = Permissions::EDIT_USER_INV;
$pNETBC = Permissions::NO_ENTRY_TO_BASE_CONTROLLER;
$pETBC = Permissions::ENTRY_TO_BASE_CONTROLLER;
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
    // Admin dashboard that can use the existing authentication middleware
    // if needed.
    Route::get('/prometheus/dashboard')
        ->middleware(fn (AC $checker) =>
            $checker->withPermission($pEI))
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
        ->action([SiteController::class, 'forgotalert'])
        ->name('site/forgotalert'),
    Route::methods([$mG, $mP], '/forgotemailfailed')
        ->action([SiteController::class, 'forgotemailfailed'])
        ->name('site/forgotemailfailed'),
    Route::methods([$mG, $mP], '/forgotusernotfound')
        ->action([SiteController::class, 'forgotusernotfound'])
        ->name('site/forgotusernotfound'),
    Route::methods([$mG, $mP], '/onetimepassworderror')
        ->action([SiteController::class, 'onetimepassworderror'])
        ->name('site/onetimepassworderror'),
    Route::methods([$mG, $mP], '/onetimepasswordfailure')
        ->action([SiteController::class, 'onetimepasswordfailure'])
        ->name('site/onetimepasswordfailure'),
    Route::methods([$mG, $mP], '/onetimepasswordsuccess')
        ->action([SiteController::class, 'onetimepasswordsuccess'])
        ->name('site/onetimepasswordsuccess'),
    Route::methods([$mG, $mP], '/privacypolicy')
        ->action([SiteController::class, 'privacypolicy'])
        ->name('site/privacypolicy'),
    Route::methods([$mG, $mP], '/resetpasswordfailed')
        ->action([SiteController::class, 'resetpasswordfailed'])
        ->name('site/resetpasswordfailed'),
    Route::methods([$mG, $mP], '/resetpasswordsuccess')
        ->action([SiteController::class, 'resetpasswordsuccess'])
        ->name('site/resetpasswordsuccess'),
    Route::methods([$mG, $mP], '/signupfailed')
        ->action([SiteController::class, 'signupfailed'])
        ->name('site/signupfailed'),
    Route::methods([$mG, $mP], '/signupsuccess')
        ->action([SiteController::class, 'signupsuccess'])
        ->name('site/signupsuccess'),
    Route::methods([$mG, $mP], '/termsofservice')
        ->action([SiteController::class, 'termsofservice'])
        ->name('site/termsofservice'),
    // Auth
    Route::methods([$mG, $mP], '/login')
        ->middleware(LRM::class)
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
        ->middleware(fn (
            AC $checker) => $checker->withPermission($pVI)
                                    ->withPermission($pNETBC))
        ->action([AuthController::class, 'regenerateCodes'])
        ->name('auth/regenerateCodes'),
    Route::methods([$mG, $mP], '/forgotpassword')
        ->middleware(fn (
            ResponseFactoryInterface $responseFactory,
            StorageInterface $storage,
        ) => new LRM(new Counter($storage, 3, 3), $responseFactory))
        ->action([ForgotPasswordController::class, 'forgot'])
        ->name('auth/forgotpassword'),
    Route::methods([$mG, $mP],
            '/resetpassword/resetpassword/{token}')
        ->action([ResetPasswordController::class, 'resetpassword'])
        ->name('auth/resetpassword'),
    // email-verification token is masked before sending by email
    // and must be unmasked after inbox click. Refer to userinv/signup
    Route::methods([$mG, $mP], '/signup')
        ->middleware(fn (
            ResponseFactoryInterface $responseFactory,
            StorageInterface $storage,
        ) => new LRM(new Counter($storage, 10, 10), $responseFactory))
        ->action([SignupController::class, 'signup'])
        ->name('auth/signup'),
    Route::methods([$mG, $mP], '/change')
        ->action([ChangePasswordController::class, 'change'])
        ->name('auth/change'),
    Group::create('/user')
        ->routes(
            // User
            Route::methods(['GET', 'POST'], '[/{page:\d+}/{pagesize:\d+}]')
                ->name('user/index')
                ->action([UserController::class, 'index']),
            // Profile page
            Route::get('/{login}')
                ->action([UserController::class, 'profile'])
                ->name('user/profile'),
        ),
    Group::create('/api')
        ->middleware(FormatDataResponseAsXml::class)
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
                ->middleware(FormatDataResponseAsJson::class)
                ->action(ApiInfo::class),
            Route::get('/user')
                ->name('api/user/index')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([ApiUserController::class, 'index']),
            Route::get('/user/{login}')
                ->name('api/user/profile')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->middleware(FormatDataResponseAsJson::class)
                ->action([ApiUserController::class, 'profile']),
        ),
    Group::create('/invoice')
        ->middleware(Authentication::class)
        ->middleware(fn (AC $checker) => $checker->withPermission($pETBC))
        ->routes(
// ************************************ pVI's *********************************
            Route::methods([$mG, $mP], '/clientnote/view/{id}')
                ->name('clientnote/view')
                ->middleware(fn (AC $checker) => $checker->withPermission($pVI))
                ->action([ClientNoteController::class, 'view']),
            Route::methods([$mG, $mP], '/contract/view/{id}')
                ->name('contract/view')
                ->middleware(fn (AC $checker) => $checker->withPermission($pVI))
                ->action([ContractController::class, 'view']),
            Route::methods([$mG, $mP], '/del/view/{id}')
                ->name('del/view')
                ->middleware(fn (AC $checker) => $checker->withPermission($pVI))
                ->action([DeliveryLocationController::class, 'view']),
            Route::methods([$mG, $mP], '/inv/pdf_dashboard_include_cf/{id}')
                ->middleware(fn (AC $checker) => $checker->withPermission($pVI))
                ->action([InvController::class, 'pdf_dashboard_include_cf'])
                ->name('inv/pdf_dashboard_include_cf'),
            Route::methods([$mG, $mP], '/inv/pdf_dashboard_exclude_cf/{id}')
                ->middleware(fn (AC $checker) => $checker->withPermission($pVI))
                ->action([InvController::class, 'pdf_dashboard_exclude_cf'])
                ->name('inv/pdf_dashboard_exclude_cf'),
            Route::methods([$mG, $mP], '/inv/pdf_download_include_cf/{url_key}')
                ->middleware(fn (AC $checker) => $checker->withPermission($pVI))
                ->action([InvController::class, 'pdf_download_include_cf'])
                ->name('inv/pdf_download_include_cf'),
            Route::methods([$mG, $mP], '/inv/pdf_download_exclude_cf/{url_key}')
                ->middleware(fn (AC $checker) => $checker->withPermission($pVI))
                ->action([InvController::class, 'pdf_download_exclude_cf'])
                ->name('inv/pdf_download_exclude_cf'),
            Route::methods([$mG, $mP], '/download_file/{upload_id}')
                ->middleware(fn (AC $checker) => $checker->withPermission($pVI))
                ->action([InvController::class, 'download_file'])
                ->name('inv/download_file'),
            // Because the inv/view is accessible to the observer and the admin
            // the inv/view function is further refined with rbac
            Route::methods([$mG, $mP], '/inv/view/{id}')
                ->name('inv/view')
                ->middleware(fn (AC $checker) => $checker->withPermission($pVI))
                ->action([InvController::class, 'view']),
            // id acquired by session
            Route::get('/client_invoices[/page/{page:\d+}[/status/{status:\d+}]]')
                ->middleware(fn (AC $checker) => $checker->withPermission($pVI))
                ->action([InvController::class, 'guest'])
                ->name('inv/guest'),
            Route::methods([$mG, $mP], '/inv/url_key/{url_key}/{gateway}')
                ->name('inv/url_key')
                ->middleware(fn (AC $checker) => $checker->withPermission($pVI))
                ->action([InvController::class, 'url_key']),
            // id acquired by session
            Route::methods([$mG, $mP], '/inv/pdf/{include}')
                ->middleware(fn (AC $checker) => $checker->withPermission($pVI))
                ->action([InvController::class, 'pdf'])
                ->name('inv/pdf'),
            // {invoice} is a complete string
            Route::methods([$mG, $mP], '/inv/download/{invoice}')
                ->middleware(fn (AC $checker) => $checker->withPermission($pVI))
                ->action([InvController::class, 'download'])
                ->name('inv/download'),
            Route::get('/invsentlog/guest')
                ->middleware(fn (AC $checker) => $checker->withPermission($pVI))
                ->action([InvSentLogController::class, 'guest'])
                ->name('invsentlog/guest'),
            Route::methods([$mG, $mP], '/invsentlog/view/{id}')
                ->name('invsentlog/view')
                ->middleware(fn (AC $checker) => $checker->withPermission($pVI))
                ->action([InvSentLogController::class, 'view']),
            Route::methods([$mG, $mP], '/invitem/view/{id}')
                ->name('invitem/view')
                ->middleware(fn (AC $checker) => $checker->withPermission($pVI))
                ->action([InvItemController::class, 'view']),
            // Add
            Route::methods([$mG, $mP], '/paymentpeppol/add/{inv_id}')
                ->middleware(fn (AC $checker) => $checker->withPermission($pVI))
                ->action([PaymentPeppolController::class, 'add'])
                ->name('paymentpeppol/add'),
            Route::get('/client_quotes[/page/{page:\d+}[/status/{status:\d+}]]')
                ->middleware(fn (AC $checker) => $checker->withPermission($pVI))
                ->action([QuoteController::class, 'guest'])
                ->name('quote/guest'),
            Route::methods([$mG, $mP], '/quote/pdf/{include}')
                ->middleware(fn (AC $checker) => $checker->withPermission($pVI))
                ->action([QuoteController::class, 'pdf'])
                ->name('quote/pdf'),
            Route::methods([$mG, $mP], '/quote/quote_to_so_confirm')
                ->middleware(fn (AC $checker) => $checker->withPermission($pVI))
                ->action([QuoteController::class, 'quote_to_so_confirm'])
                ->name('quote/quote_to_so_confirm'),
            Route::methods([$mG, $mP], '/quote/view/{id}')
                ->name('quote/view')
                ->middleware(fn (AC $checker) => $checker->withPermission($pVI))
                ->action([QuoteController::class, 'view']),
// The individual must have been give the url on the email sent and also
// have been assigned the observer role under resources/rbac/items by using
// assignRole command at command prompt
            Route::methods([$mG, $mP], '/quote/url_key/{url_key}')
                ->name('quote/url_key')
                ->middleware(fn (AC $checker) => $checker->withPermission($pVI))
                ->action([QuoteController::class, 'url_key']),
// The individual that is sent the quote approves with/without a purchase
// order number
            Route::methods([$mG, $mP], '/quote/approve')
                ->name('quote/approve')
                ->middleware(fn (AC $checker) => $checker->withPermission($pVI))
                ->action([QuoteController::class, 'approve']),
// The individual that is sent the quote rejects it.
            Route::methods([$mG, $mP], '/quote/reject/{url_key}')
                ->name('quote/reject')
                ->middleware(fn (AC $checker) => $checker->withPermission($pVI))
                ->action([QuoteController::class, 'reject']),
            Route::get(
                  '/client_salesorders[/page/{page:\d+}[/status/{status:\d+}]]')
                ->middleware(fn (AC $checker) => $checker->withPermission($pVI))
                ->action([SalesOrderController::class, 'guest'])
                ->name('salesorder/guest'),
            Route::methods([$mG, $mP], '/salesorder/agree_to_terms/{url_key}')
                ->name('salesorder/agree_to_terms')
                ->middleware(fn (AC $checker) => $checker->withPermission($pVI))
                ->action([SalesOrderController::class, 'agree_to_terms']),
            Route::methods([$mG, $mP], '/salesorder/edit/{id}')
                ->name('salesorder/edit')
                ->middleware(fn (AC $checker) => $checker->withPermission($pVI))
                ->action([SalesOrderController::class, 'edit']),
            Route::methods([$mG, $mP], '/salesorder/pdf/{include}')
                ->middleware(fn (AC $checker) => $checker->withPermission($pVI))
                ->action([SalesOrderController::class, 'pdf'])
                ->name('salesorder/pdf'),
            Route::methods([$mG, $mP], '/salesorder/reject/{url_key}')
                ->name('salesorder/reject')
                ->middleware(fn (AC $checker) => $checker->withPermission($pVI))
                ->action([SalesOrderController::class, 'reject']),
            Route::methods([$mG, $mP], '/salesorder/view/{id}')
                ->name('salesorder/view')
                ->middleware(fn (AC $checker) => $checker->withPermission($pVI))
                ->action([SalesOrderController::class, 'view']),
            Route::methods([$mG, $mP], '/salesorder/url_key/{key}')
                ->name('salesorder/url_key')
                ->middleware(fn (AC $checker) => $checker->withPermission($pVI))
                ->action([SalesOrderController::class, 'url_key']),
            Route::methods([$mG, $mP], '/salesorderitem/edit/{id}')
                ->name('salesorderitem/edit')
                ->middleware(fn (AC $checker) => $checker->withPermission($pVI))
                ->action([SalesOrderItemController::class, 'edit']),
            Route::methods([$mG, $mP],
                    '/setting/listlimit/{setting_id}/{limit}/{origin}')
                ->middleware(fn (AC $checker) => $checker->withPermission($pVI))
                ->action([SettingController::class, 'listlimit'])
                ->name('setting/listlimit'),
// *********************************** pEI's *********************************
            Route::get('')
                ->action([ICLR::class, 'index'])
                ->name('invoice/index'),
            // InvItemAllowanceCharge
            Route::get('/invitemallowancecharge')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([InvItemAllowanceChargeController::class, 'index'])
                ->name('invitemallowancecharge/index'),
            // Add
            Route::methods([$mG, $mP],
                    '/invitemallowancecharge/add/{inv_item_id}')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([InvItemAllowanceChargeController::class, 'add'])
                ->name('invitemallowancecharge/add'),
            // Edit
            Route::methods([$mG, $mP],
                    '/invitemallowancecharge/edit/{id}')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([InvItemAllowanceChargeController::class, 'edit'])
                ->name('invitemallowancecharge/edit'),
            Route::methods([$mG, $mP],
                    '/invitemallowancecharge/delete/{id}')
                ->name('invitemallowancecharge/delete')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([InvItemAllowanceChargeController::class, 'delete']),
            Route::methods([$mG, $mP],
                    '/invitemallowancecharge/view/{id}')
                ->name('invitemallowancecharge/view')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([InvItemAllowanceChargeController::class, 'view']),
            Route::get('/allowancecharge')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([AllowanceChargeController::class, 'index'])
                ->name('allowancecharge/index'),
            Route::methods([$mG, $mP],
                    '/allowancecharge/add_allowance')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([AllowanceChargeController::class, 'add_allowance'])
                ->name('allowancecharge/add_allowance'),
            Route::methods([$mG, $mP],
                    '/allowancecharge/add_charge')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([AllowanceChargeController::class, 'add_charge'])
                ->name('allowancecharge/add_charge'),
            Route::methods([$mG, $mP],
                    '/allowancecharge/edit_allowance/{id}')
                ->name('allowancecharge/edit_allowance')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([AllowanceChargeController::class, 'edit_allowance']),
            Route::methods([$mG, $mP],
                    '/allowancecharge/edit_charge/{id}')
                ->name('allowancecharge/edit_charge')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([AllowanceChargeController::class, 'edit_charge']),
            Route::methods([$mG, $mP],
                    '/allowancecharge/delete/{id}')
                ->name('allowancecharge/delete')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([AllowanceChargeController::class, 'delete']),
            Route::methods([$mG, $mP],
                    '/allowancecharge/view/{id}')
                ->name('allowancecharge/view')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([AllowanceChargeController::class, 'view']),
            // Step 1: After https://app.storecove.com/en/docs 1.1.1
            // and 1.1.2 completed
            Route::get('/store_cove_call_api')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([ICLR::class, 'store_cove_call_api'])
                ->name('invoice/store_cove_call_api'),
            // Step 2 - 1.1.4 a
            Route::get('/store_cove_call_api_get_legal_entity_id')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([ICLR::class,
                    'store_cove_call_api_get_legal_entity_id'])
                ->name('invoice/store_cove_call_api_get_legal_entity_id'),
            // Step 3a and/or LEGAL entity identifier - 1.1.4 b
            Route::get('/store_cove_call_api_legal_entity_identifier')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([ICLR::class,
                    'store_cove_call_api_legal_entity_identifier'])
                ->name('invoice/store_cove_call_api_legal_entity_identifier'),
            // Step 4 - 1.1.5
            Route::get('/store_cove_send_test_json_invoice')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([ICLR::class, 'store_cove_send_test_json_invoice'])
                ->name('invoice/store_cove_send_test_json_invoice'),
            Route::get('/dashboard')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([ICLR::class, 'dashboard'])
                ->name('invoice/dashboard'),
            Route::get('/faq/{topic}')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([ICLR::class, 'faq'])
                ->name('invoice/faq'),
            Route::get('/phpinfo/{selection}')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([ICLR::class, 'phpinfo'])
                ->name('invoice/phpinfo'),
            Route::get('/requirements')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([ICLR::class, 'requirements'])
                ->name('invoice/requirements'),
            Route::get('/test_data_remove')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([ICLR::class, 'test_data_remove'])
                ->name('invoice/test_data_remove'),
            Route::get('/test_data_reset')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([ICLR::class, 'test_data_reset'])
                ->name('invoice/test_data_reset'),
            Route::get('/setting_reset')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([ICLR::class, 'setting_reset'])
                ->name('invoice/setting_reset'),
            Route::get('/categoryprimary[/page/{page:\d+}]')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([CategoryPrimaryController::class, 'index'])
                ->name('categoryprimary/index'),
            // Add
            Route::methods([$mG, $mP], '/categoryprimary/add')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([CategoryPrimaryController::class, 'add'])
                ->name('categoryprimary/add'),
            // Edit
            Route::methods([$mG, $mP], '/categoryprimary/edit/{id}')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([CategoryPrimaryController::class, 'edit'])
                ->name('categoryprimary/edit'),
            Route::methods([$mG, $mP], '/categoryprimary/delete/{id}')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([CategoryPrimaryController::class, 'delete'])
                ->name('categoryprimary/delete'),
            Route::methods([$mG, $mP], '/categoryprimary/view/{id}')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([CategoryPrimaryController::class, 'view'])
                ->name('categoryprimary/view'),
            Route::get('/categorysecondary[/page/{page:\d+}]')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([CategorySecondaryController::class, 'index'])
                ->name('categorysecondary/index'),
            // Add
            Route::methods([$mG, $mP], '/categorysecondary/add')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([CategorySecondaryController::class, 'add'])
                ->name('categorysecondary/add'),
            // Edit
            Route::methods([$mG, $mP], '/categorysecondary/edit/{id}')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([CategorySecondaryController::class, 'edit'])
                ->name('categorysecondary/edit'),
            Route::methods([$mG, $mP], '/categorysecondary/delete/{id}')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([CategorySecondaryController::class, 'delete'])
                ->name('categorysecondary/delete'),
            Route::methods([$mG, $mP], '/categorysecondary/view/{id}')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([CategorySecondaryController::class, 'view'])
                ->name('categorysecondary/view'),
            Route::get('/client[/page/{page:\d+}[/active/{active}]]')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([ClientController::class, 'index'])
                ->name('client/index'),
            Route::methods([$mG, $mP], '/client/add/{origin}')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([ClientController::class, 'add'])
                ->name('client/add'),
            Route::methods([$mG, $mP], '/edit-a-client/{id}/{origin}')
                ->name('client/edit')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([ClientController::class, 'edit']),
            Route::methods([$mG, $mP], '/client/edit_submit')
                ->name('client/edit_submit')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([ClientController::class, 'edit_submit']),
            Route::methods([$mG, $mP], '/client/delete/{id}')
                ->name('client/delete')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([ClientController::class, 'delete']),
            Route::methods([$mG, $mP], '/client/guest')
                ->name('client/guest')
                ->middleware(fn (AC $checker) => $checker->withPermission($pECP))
                ->action([ClientController::class, 'guest']),
            Route::methods([$mG, $mP], '/client/save_client_note_new')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([ClientController::class, 'save_client_note_new'])
                ->name('client/save_client_note_new'),
            Route::methods([$mG, $mP], '/client/delete_client_note')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([ClientController::class, 'delete_client_note'])
                ->name('client/delete_client_note'),
            Route::methods([$mG, $mP],
                    '/client/view/{id}[/page/{page:\d+}[/status/{status}]]')
                ->name('client/view')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([ClientController::class, 'view']),
            Route::get('/clientpeppol')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([ClientPeppolController::class, 'index'])
                ->name('clientpeppol/index'),
            // Add
            Route::methods([$mG, $mP], '/clientpeppol/add/{client_id}')
                ->middleware(fn (AC $checker) => $checker->withPermission($pECP))
                ->action([ClientPeppolController::class, 'add'])
                ->name('clientpeppol/add'),
            // Edit
            Route::methods([$mG, $mP], '/clientpeppol/edit/{client_id}')
                ->name('clientpeppol/edit')
                ->middleware(fn (AC $checker) => $checker->withPermission($pECP))
                ->action([ClientPeppolController::class, 'edit']),
            Route::methods([$mG, $mP], '/clientpeppol/delete/{client_id}')
                ->name('clientpeppol/delete')
                ->middleware(fn (AC $checker) => $checker->withPermission($pECP))
                ->action([ClientPeppolController::class, 'delete']),
            Route::methods([$mG, $mP], '/clientpeppol/view/{client_id}')
                ->name('clientpeppol/view')
                ->middleware(fn (AC $checker) => $checker->withPermission($pECP))
                ->action([ClientPeppolController::class, 'view']),
            Route::get('/company')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([CompanyController::class, 'index'])
                ->name('company/index'),
            Route::methods([$mG, $mP], '/company/add')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([CompanyController::class, 'add'])
                ->name('company/add'),
            Route::methods([$mG, $mP], '/company/edit/{id}')
                ->name('company/edit')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([CompanyController::class, 'edit']),
            Route::methods([$mG, $mP], '/company/delete/{id}')
                ->name('company/delete')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([CompanyController::class, 'delete']),
            Route::methods([$mG, $mP], '/company/view/{id}')
                ->name('company/view')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([CompanyController::class, 'view']),
            Route::get('/companyprivate')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([CompanyPrivateController::class, 'index'])
                ->name('companyprivate/index'),
            Route::methods([$mG, $mP], '/companyprivate/add')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([CompanyPrivateController::class, 'add'])
                ->name('companyprivate/add'),
            Route::methods([$mG, $mP], '/companyprivate/edit/{id}')
                ->name('companyprivate/edit')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([CompanyPrivateController::class, 'edit']),
            Route::methods([$mG, $mP], '/companyprivate/delete/{id}')
                ->name('companyprivate/delete')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([CompanyPrivateController::class, 'delete']),
            Route::methods([$mG, $mP], '/companyprivate/view/{id}')
                ->name('companyprivate/view')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([CompanyPrivateController::class, 'view']),
            Route::get('/customfield[/page/{page:\d+}]')
                ->name('customfield/index')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([CustomFieldController::class, 'index']),
            Route::methods([$mG, $mP], '/customfield/add')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([CustomFieldController::class, 'add'])
                ->name('customfield/add'),
            Route::methods([$mG, $mP], '/customfield/edit/{id}')
                ->name('customfield/edit')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([CustomFieldController::class, 'edit']),
            Route::methods([$mG, $mP], '/customfield/delete/{id}')
                ->name('customfield/delete')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([CustomFieldController::class, 'delete']),
            Route::methods([$mG, $mP], '/customfield/view/{id}')
                ->name('customfield/view')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([CustomFieldController::class, 'view']),
            Route::get('/customvalue')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([CustomValueController::class, 'index'])
                ->name('customvalue/index'),
            Route::methods([$mG, $mP], '/customvalue/field/{id}')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([CustomValueController::class, 'field'])
                ->name('customvalue/field'),
            Route::methods([$mG, $mP], '/customvalue/new/{id}')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([CustomValueController::class, 'new'])
                ->name('customvalue/new'),
            Route::methods([$mG, $mP], '/customvalue/add')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([CustomValueController::class, 'add'])
                ->name('customvalue/add'),
            Route::methods([$mG, $mP], '/customvalue/edit/{id}')
                ->name('customvalue/edit')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([CustomValueController::class, 'edit']),
            Route::methods([$mG, $mP], '/customvalue/delete/{id}')
                ->name('customvalue/delete')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([CustomValueController::class, 'delete']),
            Route::methods([$mG, $mP], '/customvalue/view/{id}')
                ->name('customvalue/view')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([CustomValueController::class, 'view']),
            Route::get('/clientnote')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([ClientNoteController::class, 'index'])
                ->name('clientnote/index'),
            Route::methods([$mG, $mP], '/clientnote/add')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([ClientNoteController::class, 'add'])
                ->name('clientnote/add'),
            Route::methods([$mG, $mP], '/clientnote/edit/{id}')
                ->name('clientnote/edit')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([ClientNoteController::class, 'edit']),
            Route::methods([$mG, $mP], '/clientnote/delete/{id}')
                ->name('clientnote/delete')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([ClientNoteController::class, 'delete']),
            Route::get('/contract')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([ContractController::class, 'index'])
                ->name('contract/index'),
            Route::methods([$mG, $mP], '/contract/add/{client_id}')
                ->name('contract/add')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([ContractController::class, 'add']),
            Route::methods([$mG, $mP], '/contract/edit/{id}')
                ->name('contract/edit')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([ContractController::class, 'edit']),
            Route::methods([$mG, $mP], '/contract/delete/{id}')
                ->name('contract/delete')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([ContractController::class, 'delete']),
            Route::get('/del[/page/{page:\d+}]')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([DeliveryLocationController::class, 'index'])
                ->name('del/index'),
            Route::methods([$mG, $mP],
                    '/del/add/{client_id}[/{origin}/{origin_id}/{action}]')
                ->name('del/add')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([DeliveryLocationController::class, 'add']),
// arguments eg. {id},[query parameters to build a varying return url
// i.e. {origin}, {origin_id}, {action}]
            Route::methods([$mG, $mP],
                    '/del/edit/{id}[/{origin}/{origin_id}/{action}]')
                ->name('del/edit')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([DeliveryLocationController::class, 'edit']),
            Route::methods([$mG, $mP], '/del/delete/{id}')
                ->name('del/delete')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([DeliveryLocationController::class, 'delete']),
            Route::get('/delivery')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([DeliveryController::class, 'index'])
                ->name('delivery/index'),
            // Add
            Route::methods([$mG, $mP], '/delivery/add/{inv_id}')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([DeliveryController::class, 'add'])
                ->name('delivery/add'),
            // Edit
            Route::methods([$mG, $mP], '/delivery/edit/{id}')
                ->name('delivery/edit')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([DeliveryController::class, 'edit']),
            Route::methods([$mG, $mP], '/delivery/delete/{id}')
                ->name('delivery/delete')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([DeliveryController::class, 'delete']),
            Route::methods([$mG, $mP], '/delivery/view/{id}')
                ->name('delivery/view')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([DeliveryController::class, 'view']),
            Route::get('/deliveryparty')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([DeliveryPartyController::class, 'index'])
                ->name('deliveryparty/index'),
            // Add
            Route::methods([$mG, $mP], '/deliveryparty/add')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([DeliveryPartyController::class, 'add'])
                ->name('deliveryparty/add'),
            // Edit
            Route::methods([$mG, $mP], '/deliveryparty/edit/{id}')
                ->name('deliveryparty/edit')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([DeliveryPartyController::class, 'edit']),
            Route::methods([$mG, $mP], '/deliveryparty/delete/{id}')
                ->name('deliveryparty/delete')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([DeliveryPartyController::class, 'delete']),
            Route::methods([$mG, $mP], '/deliveryparty/view/{id}')
                ->name('deliveryparty/view')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([DeliveryPartyController::class, 'view']),
            Route::get('/emailtemplate')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([EmailTemplateController::class, 'index'])
                ->name('emailtemplate/index'),
            Route::methods([$mG, $mP], '/emailtemplate/add_invoice')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([EmailTemplateController::class, 'add_invoice'])
                ->name('emailtemplate/add_invoice'),
            Route::methods([$mG, $mP],
                    '/emailtemplate/edit_invoice/{email_template_id}')
                ->name('emailtemplate/edit_invoice')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([EmailTemplateController::class, 'edit_invoice']),
            Route::methods([$mG, $mP], '/emailtemplate/add_quote')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([EmailTemplateController::class, 'add_quote'])
                ->name('emailtemplate/add_quote'),
            Route::methods([$mG, $mP],
                    '/emailtemplate/edit_quote/{email_template_id}')
                ->name('emailtemplate/edit_quote')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([EmailTemplateController::class, 'edit_quote']),
            Route::methods([$mG, $mP],
                    '/emailtemplate/delete/{email_template_id}')
                ->name('emailtemplate/delete')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([EmailTemplateController::class, 'delete']),
            Route::methods([$mG, $mP],
                    '/emailtemplate/get_content/{email_template_id}')
                ->name('emailtemplate/get_content')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([EmailTemplateController::class, 'get_content']),
            Route::methods([$mG, $mP],
                    '/emailtemplate/preview/{email_template_id}')
                ->name('emailtemplate/preview')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([EmailTemplateController::class, 'preview']),
            Route::methods([$mG, $mP], '/emailtemplate/view/{email_template_id}')
                ->name('emailtemplate/view')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([EmailTemplateController::class, 'view']),
            Route::get('/family[/page/{page:\d+}]')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([FamilyController::class, 'index'])
                ->name('family/index'),
            Route::methods([$mG, $mP], '/family/test')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->middleware(FormatDataResponseAsJson::class)
                ->action([FamilyController::class])
                ->name('family/test'),
            Route::methods([$mG, $mP], '/family/add')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([FamilyController::class, 'add'])
                ->name('family/add'),
            Route::methods([$mG, $mP], '/family/edit/{id}')
                ->name('family/edit')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([FamilyController::class, 'edit']),
            Route::methods([$mG, $mP], '/family/delete/{id}')
                ->name('family/delete')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([FamilyController::class, 'delete']),
            // Dependency Dropdown form Load _search form
            Route::methods([$mG, $mP], '/family/search')
                ->name('family/search')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([FamilyController::class, 'search']),
            // Dependency Dropdown form Load CategorySecondary items after
            // category_primary_id selection
            Route::methods([$mG, $mP],
                    '/family/secondaries/{category_primary_id}')
                ->name('family/secondaries')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([FamilyController::class, 'secondaries']),
            // Dependency Dropdown form Load Family Names
            Route::methods([$mG, $mP], '/family/names/{category_secondary_id}')
                ->name('family/names')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([FamilyController::class, 'names']),
            // Dependency Dropdown form Load Products associated with family id
            Route::methods([$mG, $mP], '/family/products/{id}')
                ->name('family/products')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([FamilyController::class, 'products']),
            Route::methods([$mG, $mP], '/family/view/{id}')
                ->name('family/view')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([FamilyController::class, 'view']),
            // Generate products from selected families
            Route::methods([$mG, $mP], '/family/generate_products')
                ->name('family/generate_products')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([FamilyController::class, 'generate_products']),
            Route::get('/from')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([FromDropDownController::class, 'index'])
                ->name('from/index'),
            Route::methods([$mG, $mP], '/from/add')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([FromDropDownController::class, 'add'])
                ->name('from/add'),
            Route::methods([$mG, $mP], '/from/edit/{id}')
                ->name('from/edit')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([FromDropDownController::class, 'edit']),
            Route::methods([$mG, $mP], '/from/delete/{id}')
                ->name('from/delete')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([FromDropDownController::class, 'delete']),
            Route::methods([$mG, $mP], '/from/view/{id}')
                ->name('from/view')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([FromDropDownController::class, 'view']),
            Route::get('/generator')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([GeneratorController::class, 'index'])
                ->name('generator/index'),
            Route::methods([$mG, $mP], '/generator/add')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([GeneratorController::class, 'add'])
                ->name('generator/add'),
            Route::methods([$mG, $mP], '/generator/edit/{id}')
                ->name('generator/edit')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([GeneratorController::class, 'edit']),
            Route::methods([$mG, $mP], '/generator/delete/{id}')
                ->name('generator/delete')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([GeneratorController::class, 'delete']),
            Route::methods([$mG, $mP], '/generator/view/{id}')
                ->name('generator/view')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([GeneratorController::class, 'view']),
            Route::methods([$mG, $mP], '/generator/entity/{id}')
                ->name('generator/entity')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([GeneratorController::class, 'entity']),
            Route::methods([$mG, $mP], '/generator/repo/{id}')
                ->name('generator/repo')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([GeneratorController::class, 'repo']),
            Route::methods([$mG, $mP], '/generator/service/{id}')
                ->name('generator/service')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([GeneratorController::class, 'service']),
            Route::methods([$mG, $mP], '/generator/mapper/{id}')
                ->name('generator/mapper')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([GeneratorController::class, 'mapper']),
            Route::methods([$mG, $mP], '/generator/controller/{id}')
                ->name('generator/controller')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([GeneratorController::class, 'controller']),
            Route::methods([$mG, $mP], '/generator/form/{id}')
                ->name('generator/form')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([GeneratorController::class, 'form']),
            Route::methods([$mG, $mP], '/generator/scope/{id}')
                ->name('generator/scope')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([GeneratorController::class, 'scope']),
            Route::methods([$mG, $mP], '/generator/_index/{id}')
                ->name('generator/_index')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([GeneratorController::class, '_index']),
            Route::methods([$mG, $mP], '/generator/_index_adv_paginator/{id}')
                ->name('generator/_index_adv_paginator')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([GeneratorController::class, '_index_adv_paginator']),
            Route::methods([$mG, $mP],
                    '/generator/_index_adv_paginator_with_filter/{id}')
                ->name('generator/_index_adv_paginator_with_filter')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([GeneratorController::class,
                    '_index_adv_paginator_with_filter']),
            Route::methods([$mG, $mP], '/generator/_form/{id}')
                ->name('generator/_form')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([GeneratorController::class, '_form']),
            Route::methods([$mG, $mP], '/generator/_view/{id}')
                ->name('generator/_view')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([GeneratorController::class, '_view']),
            Route::methods([$mG, $mP], '/generator/_route/{id}')
                ->name('generator/_route')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([GeneratorController::class, '_route']),
            Route::methods([$mG, $mP], '/generator/quick_view_schema')
                ->name('generator/quick_view_schema')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([GeneratorController::class, 'quick_view_schema']),
// type = eg. 'app', or 'diff'
// Translate either app_lang, diff_lang.php in src/Invoice/Language/English
// using Setting google_translate_locale under Settings...
// View...Google Translate
            Route::methods([$mG, $mP], '/generator/google_translate_lang/{type}')
                ->name('generator/google_translate_lang')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([GeneratorController::class, 'google_translate_lang']),
// Translate info documentation files like invoice.php
// from resources/views/invoice/info/en/invoice.php to target language folder
            Route::methods([$mG, $mP], '/generator/google_translate_info')
                ->name('generator/google_translate_info')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([GeneratorController::class, 'google_translate_info']),
            Route::get('/generatorrelation')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([GeneratorRelationController::class, 'index'])
                ->name('generatorrelation/index'),
            Route::methods([$mG, $mP], '/generatorrelation/add')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([GeneratorRelationController::class, 'add'])
                ->name('generatorrelation/add'),
            Route::methods([$mG, $mP], '/generatorrelation/edit/{id}')
                ->name('generatorrelation/edit')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([GeneratorRelationController::class, 'edit']),
            Route::methods([$mG, $mP], '/generatorrelation/delete/{id}')
                ->name('generatorrelation/delete')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([GeneratorRelationController::class, 'delete']),
            Route::methods([$mG, $mP], '/generatorrelation/view/{id}')
                ->name('generatorrelation/view')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([GeneratorRelationController::class, 'view']),
            Route::get('/group')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([GroupController::class, 'index'])
                ->name('group/index'),
            Route::methods([$mG, $mP], '/group/add')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([GroupController::class, 'add'])
                ->name('group/add'),
            Route::methods([$mG, $mP], '/group/edit/{id}')
                ->name('group/edit')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([GroupController::class, 'edit']),
            Route::methods([$mG, $mP], '/group/delete/{id}')
                ->name('group/delete')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([GroupController::class, 'delete']),
            Route::methods([$mG, $mP], '/group/view/{id}')
                ->name('group/view')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([GroupController::class, 'view']),
            Route::methods([$mG, $mP], '/import/index')
                ->name('import/index')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([ImportController::class, 'index']),
            Route::methods([$mG, $mP], '/import/testconnection')
                ->name('import/testconnection')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([ImportController::class, 'testConnection']),
            Route::methods([$mG, $mP], '/import/invoiceplane')
                ->name('import/invoiceplane')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([ImportController::class, 'invoiceplane']),
            Route::get('/inv[/page/{page:\d+}[/status/{status:\d+}]]')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([InvController::class, 'index'])
                ->name('inv/index'),
            Route::get('/inv/[/status/{status:\d+}]')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([InvController::class, 'indexMark'])
                ->name('inv/indexmark'),
            Route::methods([$mG, $mP], '/inv/peppol_stream_toggle/{id}')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([InvController::class, 'peppol_stream_toggle'])
                ->name('inv/peppol_stream_toggle'),
            Route::methods([$mG, $mP], '/inv/peppol_doc_currency_toggle/{id}')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([InvController::class, 'peppol_doc_currency_toggle'])
                ->name('inv/peppol_doc_currency_toggle'),
            Route::methods([$mG, $mP], '/archive')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([InvController::class, 'archive'])
                ->name('inv/archive'),
            Route::methods([$mG, $mP], '/inv/save_custom')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([InvController::class, 'save_custom'])
                ->name('inv/save_custom'),
            Route::methods([$mG, $mP], '/inv/save_inv_allowance_charge')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([InvController::class, 'save_inv_allowance_charge'])
                ->name('inv/save_inv_allowance_charge'),
            Route::methods([$mG, $mP], '/inv/save_inv_tax_rate')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([InvController::class, 'save_inv_tax_rate'])
                ->name('inv/save_inv_tax_rate'),
            Route::methods([$mG, $mP], '/inv/delete_inv_tax_rate/{id}')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([InvController::class, 'delete_inv_tax_rate'])
                ->name('inv/delete_inv_tax_rate'),
            Route::methods([$mG, $mP], '/inv/delete_inv_item/{id}')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([InvController::class, 'delete_inv_item'])
                ->name('inv/delete_inv_item'),
            Route::methods([$mG, $mP], '/inv/email_stage_0/{id}')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([InvController::class, 'email_stage_0'])
                ->name('inv/email_stage_0'),
            Route::methods([$mG, $mP], '/inv/email_stage_2/{id}')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([InvController::class, 'email_stage_2'])
                ->name('inv/email_stage_2'),
            Route::methods([$mG, $mP], '/inv/mark_as_sent')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([InvController::class, 'mark_as_sent'])
                ->name('inv/mark_as_sent'),
            Route::methods([$mG, $mP], '/inv/mark_sent_as_draft')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([InvController::class, 'mark_sent_as_draft'])
                ->name('inv/mark_sent_as_draft'),
            Route::methods([$mG, $mP], '/inv/modal_change_client')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([InvController::class, 'modal_change_client'])
                ->name('inv/modal_change_client'),
            Route::methods([$mG, $mP], '/inv/attachment/{id}')
                ->name('inv/attachment')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([InvController::class, 'attachment']),
            Route::methods([$mG, $mP], '/inv/edit/{id}')
                ->name('inv/edit')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([InvController::class, 'edit']),
            Route::methods([$mG, $mP], '/inv/flush')
                ->name('inv/flush')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([InvController::class, 'flush']),
            Route::methods([$mG, $mP], '/inv/peppol/{id}')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([InvController::class, 'peppol'])
                ->name('inv/peppol'),
            Route::methods([$mG, $mP], '/inv/storecove/{id}')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([InvController::class, 'storecove'])
                ->name('inv/storecove'),
            Route::methods([$mG, $mP], '/inv/test')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([InvController::class, 'test'])
                ->name('inv/test'),
            Route::methods([$mG, $mP], '/inv/save')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([InvController::class, 'save'])
                ->name('inv/save'),
            Route::methods([$mG, $mP], '/inv/delete/{id}')
                ->name('inv/delete')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([InvController::class, 'delete']),
            Route::methods([$mG, $mP], '/inv/html/{include}')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([InvController::class, 'html'])
                ->name('inv/html'),
            Route::methods([$mG, $mP], '/inv/save_inv_item')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([InvController::class, 'save_inv_item'])
                ->name('inv/save_inv_item'),
            Route::methods([$mG, $mP], '/inv/modalcreate')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([InvController::class, 'modalcreate'])
                ->name('inv/modalcreate'),
            Route::methods([$mG, $mP], '/inv/multiplecopy')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([InvController::class, 'multiplecopy'])
                ->name('inv/multiplecopy'),
            Route::methods([$mG, $mP], '/inv/confirm')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([InvController::class, 'confirm'])
                ->name('inv/confirm'),
            Route::methods([$mG, $mP], '/inv/add/{origin}')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([InvController::class, 'add'])
                ->name('inv/add'),
            Route::methods([$mG, $mP], '/inv/create_confirm')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([InvController::class, 'create_confirm'])
                ->name('inv/create_confirm'),
            Route::methods([$mG, $mP], '/inv/create_credit_confirm')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([InvController::class, 'create_credit_confirm'])
                ->name('inv/create_credit_confirm'),
            Route::methods([$mG, $mP], '/inv/inv_to_inv_confirm')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([InvController::class, 'inv_to_inv_confirm'])
                ->name('inv/inv_to_inv_confirm'),
            // InvAllowanceCharge
            Route::get('/invallowancecharge[/page/{page:\d+}]')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([InvAllowanceChargeController::class, 'index'])
                ->name('invallowancecharge/index'),
            // Add
            Route::methods([$mG, $mP], '/invallowancecharge/add/{inv_id}')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([InvAllowanceChargeController::class, 'add'])
                ->name('invallowancecharge/add'),
            // Edit
            Route::methods([$mG, $mP], '/invallowancecharge/edit/{id}')
                ->name('invallowancecharge/edit')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([InvAllowanceChargeController::class, 'edit']),
            Route::methods([$mG, $mP], '/invallowancecharge/delete/{id}')
                ->name('invallowancecharge/delete')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([InvAllowanceChargeController::class, 'delete']),
            Route::methods([$mG, $mP], '/invallowancecharge/view/{id}')
                ->name('invallowancecharge/view')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([InvAllowanceChargeController::class, 'view']),
            // InvRecurring
            Route::get('/invrecurring')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([InvRecurringController::class, 'index'])
                ->name('invrecurring/index'),
            // Add
            Route::methods([$mG, $mP], '/invrecurring/add/{inv_id}')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([InvRecurringController::class, 'add'])
                ->name('invrecurring/add'),
            // Create via inv.js create_recurring_confirm
            Route::methods([$mG, $mP], '/invrecurring/multiple')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([InvRecurringController::class, 'multiple'])
                ->name('invrecurring/multiple'),
            Route::methods([$mG, $mP], '/invrecurring/get_recur_start_date')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([InvRecurringController::class, 'get_recur_start_date'])
                ->name('invrecurring/get_recur_start_date'),
            // Edit
            Route::methods([$mG, $mP], '/invrecurring/start/{id}')
                ->name('invrecurring/start')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([InvRecurringController::class, 'start']),
            Route::methods([$mG, $mP], '/invrecurring/delete/{id}')
                ->name('invrecurring/delete')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([InvRecurringController::class, 'delete']),
            Route::methods([$mG, $mP], '/invrecurring/stop/{id}')
                ->name('invrecurring/stop')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([InvRecurringController::class, 'stop']),
            Route::methods([$mG, $mP], '/invrecurring/view/{id}')
                ->name('invrecurring/view')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([InvRecurringController::class, 'view']),
            Route::get('/invsentlog')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([InvSentLogController::class, 'index'])
                ->name('invsentlog/index'),
            Route::methods([$mP], '/invitem/add_product')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([InvItemController::class, 'add_product'])
                ->name('invitem/add_product'),
            Route::methods([$mP], '/invitem/add_task')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([InvItemController::class, 'add_task'])
                ->name('invitem/add_task'),
            Route::methods([$mG, $mP], '/invitem/edit_product/{id}')
                ->name('invitem/edit_product')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([InvItemController::class, 'edit_product']),
            Route::methods([$mG, $mP], '/invitem/edit_task/{id}')
                ->name('invitem/edit_task')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([InvItemController::class, 'edit_task']),
            Route::methods([$mG, $mP], '/invitem/delete/{id}')
                ->name('invitem/delete')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([InvItemController::class, 'delete']),
            Route::methods([$mG, $mP], '/invitem/multiple')
                ->name('invitem/multiple')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([InvItemController::class, 'multiple']),
            Route::get('/itemlookup')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([ItemLookupController::class, 'index'])
                ->name('itemlookup/index'),
            Route::methods([$mG, $mP], '/itemlookup/add')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([ItemLookupController::class, 'add'])
                ->name('itemlookup/add'),
            Route::methods([$mG, $mP], '/itemlookup/edit/{id}')
                ->name('itemlookup/edit')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([ItemLookupController::class, 'edit']),
            Route::methods([$mG, $mP], '/itemlookup/delete/{id}')
                ->name('itemlookup/delete')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([ItemLookupController::class, 'delete']),
            Route::methods([$mG, $mP], '/itemlookup/view/{id}')
                ->name('itemlookup/view')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([ItemLookupController::class, 'view']),
            Route::methods([$mG, $mP],
                    '/paymentinformation/amazon_complete/{url_key}')
                ->middleware(fn (AC $checker) => $checker->withPermission($pVP))
                ->action([PICLR::class, 'amazon_complete'])
                ->name('paymentinformation/amazon_complete'),
            Route::methods([$mG, $mP],
                    '/paymentinformation/braintree_complete/{url_key}')
                ->middleware(fn (AC $checker) => $checker->withPermission($pVP))
                ->action([PICLR::class, 'braintree_complete'])
                ->name('paymentinformation/braintree_complete'),
            Route::methods([$mG, $mP],
                    '/paymentinformation/mollie_complete/{url_key}')
                ->middleware(fn (AC $checker) => $checker->withPermission($pVP))
                ->action([PICLR::class, 'mollie_complete'])
                ->name('paymentinformation/mollie_complete'),
            Route::methods([$mG, $mP],
                    '/paymentinformation/openbanking_oauth_complete/{url_key}')
                ->middleware(fn (AC $checker) => $checker->withPermission($pVP))
                ->action([PICLR::class, 'openbanking_oauth_complete'])
                ->name('paymentinformation/openbanking_oauth_complete'),
            Route::methods([$mG, $mP],
                    '/paymentinformation/openbanking_token_complete/{url_key}')
                ->middleware(fn (AC $checker) => $checker->withPermission($pVP))
                ->action([PICLR::class, 'openbanking_token_complete'])
                ->name('paymentinformation/openbanking_token_complete'),
            Route::methods([$mG, $mP],
                    '/paymentinformation/stripe_complete/{url_key}')
                ->middleware(fn (AC $checker) => $checker->withPermission($pVP))
                ->action([PICLR::class, 'stripe_complete'])
                ->name('paymentinformation/stripe_complete'),
            Route::methods([$mG, $mP],
                    '/paymentinformation/stripe_incomplete/{url_key}')
                ->middleware(fn (AC $checker) => $checker->withPermission($pVP))
                ->action([PICLR::class, 'stripe_incomplete'])
                ->name('paymentinformation/stripe_incomplete'),
            Route::methods([$mG, $mP],
                    '/paymentinformation/wonderful_complete/{url_key}/{ref}')
                ->middleware(fn (AC $checker) => $checker->withPermission($pVP))
                ->action([PICLR::class, 'wonderful_complete'])
                ->name('paymentinformation/wonderful_complete'),
            Route::methods([$mG, $mP],
             '/paymentinformation/tink_complete/{url_key}/{payment_request_id}')
                ->middleware(fn (AC $checker) => $checker->withPermission($pVP))
                ->action([PICLR::class, 'tink_complete'])
                ->name('paymentinformation/tink_complete'),
            Route::methods([$mG, $mP], '/paymentinformation/fetch')
                ->middleware(fn (AC $checker) => $checker->withPermission($pVP))
                ->action([PICLR::class, 'fetch'])
                ->name('paymentinformation/fetch'),
            Route::methods([$mG, $mP],
                    '/paymentinformation/inform/{url_key}/{gateway}')
                ->middleware(fn (AC $checker) => $checker->withPermission($pVP))
                ->action([PICLR::class, 'inform'])
                ->name('paymentinformation/inform'),
            Route::get('/paymentpeppol')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([PaymentPeppolController::class, 'index'])
                ->name('paymentpeppol/index'),
            // Edit
            Route::methods([$mG, $mP], '/paymentpeppol/edit/{id}')
                ->name('paymentpeppol/edit')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([PaymentPeppolController::class, 'edit']),
            Route::methods([$mG, $mP], '/paymentpeppol/delete/{id}')
                ->name('paymentpeppol/delete')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([PaymentPeppolController::class, 'delete']),
            Route::methods([$mG, $mP], '/paymentpeppol/view/{id}')
                ->name('paymentpeppol/view')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([PaymentPeppolController::class, 'view']),
            // PostalAddress
            Route::get('/postaladdress')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->name('postaladdress/index')
                ->action([PostalAddressController::class, 'index']),
            // Add
            Route::methods([$mG, $mP],
                '/postaladdress/add/{client_id}[/{origin}/{origin_id}/{action}]')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([PostalAddressController::class, 'add'])
                ->name('postaladdress/add'),
            // Edit
            Route::methods([$mG, $mP],
                '/postaladdress/edit/{id}[/{origin}/{origin_id}/{action}]')
                ->name('postaladdress/edit')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([PostalAddressController::class, 'edit']),
            Route::methods([$mG, $mP], '/postaladdress/delete/{id}')
                ->name('postaladdress/delete')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([PostalAddressController::class, 'delete']),
            Route::methods([$mG, $mP], '/postaladdress/view/{id}')
                ->name('postaladdress/view')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([PostalAddressController::class, 'view']),
            Route::get('/product[/page/{page:\d+}]')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([ProductController::class, 'index'])
                ->name('product/index'),
            Route::get('/product/search')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([ProductController::class, 'search'])
                ->name('product/search'),
            Route::methods([$mG, $mP], '/product/test')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->middleware(FormatDataResponseAsJson::class)
                ->action([ProductController::class, 'test'])
                ->name('product/test'),
            Route::methods([$mG, $mP], '/product/add')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([ProductController::class, 'add'])
                ->name('product/add'),
            Route::methods([$mG, $mP], '/product/lookup')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([ProductController::class, 'lookup'])
                ->name('product/lookup'),
            Route::get('/product/selection_quote')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([ProductController::class, 'selection_quote'])
                ->name('product/selection_quote'),
            Route::get('/product/selection_inv')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([ProductController::class, 'selection_inv'])
                ->name('product/selection_inv'),
            Route::methods([$mG, $mP], '/product/edit/{id}')
                ->name('product/edit')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([ProductController::class, 'edit']),
            Route::methods([$mG, $mP], '/product/delete/{id}')
                ->name('product/delete')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([ProductController::class, 'delete']),
            Route::methods([$mG, $mP], '/product/view/{id}')
                ->name('product/view')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([ProductController::class, 'view']),
            Route::methods([$mG, $mP], '/image/{product_image_id}')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([ProductController::class, 'download_image_file'])
                ->name('product/download_image_file'),
            Route::methods([$mG, $mP], '/image_attachment/{id}')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([ProductController::class, 'image_attachment'])
                ->name('product/image_attachment'),
            // ProductClient
            Route::methods([$mG, $mP], '/productclient/add')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([ProductClientController::class, 'add'])
                ->name('productclient/add'),
            Route::methods([$mG, $mP], '/productclient/associate-multiple')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([ProductClientController::class, 'associateMultiple'])
                ->name('productclient/associate-multiple'),
            Route::methods([$mG, $mP], '/productclient/edit/{id}')
                ->name('productclient/edit')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([ProductClientController::class, 'edit']),
            Route::methods([$mG, $mP], '/productclient/delete/{id}')
                ->name('productclient/delete')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([ProductClientController::class, 'delete']),
            Route::methods([$mG, $mP], '/productclient/view/{id}')
                ->name('productclient/view')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([ProductClientController::class, 'view']),
                
            Route::get('/productimage')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([ProductImageController::class, 'index'])
                ->name('productimage/index'),
            Route::methods([$mG, $mP], '/productimage/add/{product_id}')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([ProductImageController::class, 'add'])
                ->name('productimage/add'),
            Route::methods([$mG, $mP], '/productimage/edit/{id}')
                ->name('productimage/edit')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([ProductImageController::class, 'edit']),
            Route::methods([$mG, $mP], '/productimage/delete/{id}')
                ->name('productimage/delete')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([ProductImageController::class, 'delete']),
            Route::methods([$mG, $mP], '/productimage/view/{id}')
                ->name('productimage/view')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([ProductImageController::class, 'view']),
            // ProductProperty
            Route::get('/productproperty')
                ->name('productproperty/index')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([ProductPropertyController::class, 'index']),
            // Add
            Route::methods([$mG, $mP], '/productproperty/add/{product_id}')
                ->name('productproperty/add')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([ProductPropertyController::class, 'add']),
            // Edit
            Route::methods([$mG, $mP], '/productproperty/edit/{id}')
                ->name('productproperty/edit')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([ProductPropertyController::class, 'edit']),
            Route::methods([$mG, $mP], '/productproperty/delete/{id}')
                ->name('productproperty/delete')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([ProductPropertyController::class, 'delete']),
            Route::methods([$mG, $mP], '/productproperty/view/{id}')
                ->name('productproperty/view')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([ProductPropertyController::class, 'view']),
            Route::get('/profile')
                ->name('profile/index')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([ProfileController::class, 'index']),
            Route::methods([$mG, $mP], '/profile/add')
                ->name('profile/add')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([ProfileController::class, 'add']),
            Route::methods([$mG, $mP], '/profile/edit/{id}')
                ->name('profile/edit')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([ProfileController::class, 'edit']),
            Route::methods([$mG, $mP], '/profile/delete/{id}')
                ->name('profile/delete')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([ProfileController::class, 'delete']),
            Route::methods([$mG, $mP], '/profile/view/{id}')
                ->name('profile/view')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([ProfileController::class, 'view']),
            Route::get('/project[/page/{page:\d+}]')
                ->name('project/index')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([ProjectController::class, 'index']),
            Route::methods([$mG, $mP], '/project/add')
                ->name('project/add')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([ProjectController::class, 'add']),
            Route::methods([$mG, $mP], '/project/edit/{id}')
                ->name('project/edit')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([ProjectController::class, 'edit']),
            Route::methods([$mG, $mP], '/project/delete/{id}')
                ->name('project/delete')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([ProjectController::class, 'delete']),
            Route::methods([$mG, $mP], '/project/view/{id}')
                ->name('project/view')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([ProjectController::class, 'view']),
            Route::get('/merchant')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([MerchantController::class, 'index'])
                ->name('merchant/index'),
            Route::methods([$mG, $mP], '/merchant/add')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([MerchantController::class, 'add'])
                ->name('merchant/add'),
            Route::methods([$mG, $mP], '/merchant/edit/{id}')
                ->name('merchant/edit')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([MerchantController::class, 'edit']),
            Route::methods([$mG, $mP], '/merchant/delete/{id}')
                ->name('merchant/delete')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([MerchantController::class, 'delete']),
            Route::methods([$mG, $mP], '/merchant/view/{id}')
                ->name('merchant/view')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([MerchantController::class, 'view']),
            Route::methods([$mG, $mP], '/payment/add')
                ->name('payment/add')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([PaymentController::class, 'add']),
            Route::methods([$mG, $mP], '/payment/edit/{id}')
                ->name('payment/edit')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([PaymentController::class, 'edit']),
            Route::methods([$mG, $mP], '/payment/delete/{id}')
                ->name('payment/delete')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([PaymentController::class, 'delete']),
            Route::methods([$mG, $mP], '/payment/view/{id}')
                ->name('payment/view')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([PaymentController::class, 'view']),
            Route::get('/payment[/page/{page:\d+}]')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEP))
                ->action([PaymentController::class, 'index'])
                ->name('payment/index'),
            Route::get('/user_client_payments[/page/{page:\d+}]')
                ->middleware(fn (AC $checker) => $checker->withPermission($pVP))
                ->action([PaymentController::class, 'guest'])
                ->name('payment/guest'),
            Route::get('/online_log[/page/{page:\d+}]')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEP))
                ->action([PaymentController::class, 'online_log'])
                ->name('payment/online_log'),
            Route::get('/guest_online_log[/page/{page:\d+}]')
                ->middleware(fn (AC $checker) => $checker->withPermission($pVP))
                ->action([PaymentController::class, 'guest_online_log'])
                ->name('payment/guest_online_log'),
            Route::get('/paymentmethod')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([PaymentMethodController::class, 'index'])
                ->name('paymentmethod/index'),
            Route::methods([$mG, $mP], '/paymentmethod/add')
                ->name('paymentmethod/add')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([PaymentMethodController::class, 'add']),
            Route::methods([$mG, $mP], '/paymentmethod/edit/{id}')
                ->name('paymentmethod/edit')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([PaymentMethodController::class, 'edit']),
            Route::methods([$mG, $mP], '/paymentmethod/delete/{id}')
                ->name('paymentmethod/delete')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([PaymentMethodController::class, 'delete']),
            Route::methods([$mG, $mP], '/paymentmethod/view/{id}')
                ->name('paymentmethod/view')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([PaymentMethodController::class, 'view']),
            Route::methods([$mG, $mP], '/quote/add/{origin}')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([QuoteController::class, 'add'])
                ->name('quote/add'),
            Route::methods([$mG, $mP], '/quote/email_stage_0/{id}')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([QuoteController::class, 'email_stage_0'])
                ->name('quote/email_stage_0'),
            Route::methods([$mG, $mP], '/quote/email_stage_2/{id}')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([QuoteController::class, 'email_stage_2'])
                ->name('quote/email_stage_2'),
            Route::get('/quote[/page/{page:\d+}[/status/{status:\d+}]]')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([QuoteController::class, 'index'])
                ->name('quote/index'),
            Route::methods([$mG, $mP], '/quote/save_custom')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([QuoteController::class, 'save_custom'])
                ->name('quote/save_custom'),
            Route::methods([$mG, $mP], '/quote/save_quote_tax_rate')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([QuoteController::class, 'save_quote_tax_rate'])
                ->name('quote/save_quote_tax_rate'),
            Route::methods([$mG, $mP], '/quote/delete_quote_tax_rate/{id}')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([QuoteController::class, 'delete_quote_tax_rate'])
                ->name('quote/delete_quote_tax_rate'),
            Route::methods([$mG, $mP], '/quote/delete_quote_item/{id}')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([QuoteController::class, 'delete_quote_item'])
                ->name('quote/delete_quote_item'),
            Route::methods([$mG, $mP], '/quote/pdf_dashboard_include_cf/{id}')
                ->middleware(fn (AC $checker) => $checker->withPermission($pVI))
                ->action([QuoteController::class, 'pdf_dashboard_include_cf'])
                ->name('quote/pdf_dashboard_include_cf'),
            Route::methods([$mG, $mP], '/quote/pdf_dashboard_exclude_cf/{id}')
                ->middleware(fn (AC $checker) => $checker->withPermission($pVI))
                ->action([QuoteController::class, 'pdf_dashboard_exclude_cf'])
                ->name('quote/pdf_dashboard_exclude_cf'),
            Route::methods([$mG, $mP], '/quote/modalcreate')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([QuoteController::class, 'modalcreate'])
                ->name('quote/modalcreate'),
            Route::methods([$mG, $mP], '/quote/confirm')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([QuoteController::class, 'confirm'])
                ->name('quote/confirm'),
            Route::methods([$mG, $mP], '/quote/create_confirm')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([QuoteController::class, 'create_confirm'])
                ->name('quote/create_confirm'),
            Route::methods([$mG, $mP], '/quote/quote_to_invoice_confirm')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([QuoteController::class, 'quote_to_invoice_confirm'])
                ->name('quote/quote_to_invoice_confirm'),
            Route::methods([$mG, $mP], '/quote/quote_to_quote_confirm')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([QuoteController::class, 'quote_to_quote_confirm'])
                ->name('quote/quote_to_quote_confirm'),
            Route::methods([$mG, $mP], '/quote/modal_change_client')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([QuoteController::class, 'modal_change_client'])
                ->name('quote/modal_change_client'),
            Route::methods([$mG, $mP], '/quote/edit/{id}')
                ->name('quote/edit')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([QuoteController::class, 'edit']),
            Route::methods([$mG, $mP], '/quote/test')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([QuoteController::class, 'test'])
                ->name('quote/test'),
            Route::methods([$mG, $mP], '/quote/save')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([QuoteController::class, 'save'])
                ->name('quote/save'),
            Route::methods([$mG, $mP], '/quote/delete/{id}')
                ->name('quote/delete')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([QuoteController::class, 'delete']),
            Route::methods([$mG, $mP], '/quote/generate_quote_pdf/{url_key}')
                ->name('quote/generate_quote_pdf')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([QuoteController::class, 'generate_quote_pdf']),
            // QuoteAllowanceCharge
            Route::get('/quoteallowancecharge[/page/{page:\d+}]')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([QuoteAllowanceChargeController::class, 'index'])
                ->name('quoteallowancecharge/index'),
            // Add
            Route::methods([$mG, $mP], '/quoteallowancecharge/add/{quote_id}')
                ->action([QuoteAllowanceChargeController::class, 'add'])
                ->name('quoteallowancecharge/add'),
            // Edit
            Route::methods([$mG, $mP], '/quoteallowancecharge/edit/{id}')
                ->name('quoteallowancecharge/edit')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([QuoteAllowanceChargeController::class, 'edit']),
            Route::methods([$mG, $mP], '/quoteallowancecharge/delete/{id}')
                ->name('quoteallowancecharge/delete')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([QuoteAllowanceChargeController::class, 'delete']),
            Route::methods([$mG, $mP], '/quoteallowancecharge/view/{id}')
                ->name('quoteallowancecharge/view')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([QuoteAllowanceChargeController::class, 'view']),
            Route::get('/quoteitem')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([QuoteItemController::class, 'index'])
                ->name('quoteitem/index'),
            Route::methods([$mP], '/quoteitem/add_product')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([QuoteItemController::class, 'add_product'])
                ->name('quoteitem/add_product'),
            Route::methods([$mP], '/quoteitem/add_task')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([QuoteItemController::class, 'add_task'])
                ->name('quoteitem/add_task'),
            Route::methods([$mG, $mP], '/quoteitem/edit_product/{id}')
                ->name('quoteitem/edit_product')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([QuoteItemController::class, 'edit_product']),
            Route::methods([$mG, $mP], '/quoteitem/edit_task/{id}')
                ->name('quoteitem/edit_task')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([QuoteItemController::class, 'edit_task']),
            Route::methods([$mG, $mP], '/quoteitem/delete/{id}')
                ->name('quoteitem/delete')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([QuoteItemController::class, 'delete']),
            Route::methods([$mG, $mP], '/quoteitem/multiple')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([QuoteItemController::class, 'multiple'])
                ->name('quoteitem/delete_multiple'),
            Route::methods([$mG, $mP], '/quoteitem/view/{id}')
                ->name('quoteitem/view')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([QuoteItemController::class, 'view']),
                
            // QuoteItemAllowanceCharge
            Route::get('/quoteitemallowancecharge')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([QuoteItemAllowanceChargeController::class, 'index'])
                ->name('quoteitemallowancecharge/index'),
            // Add
            Route::methods([$mG, $mP],
                    '/quoteitemallowancecharge/add/{quote_item_id}')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([QuoteItemAllowanceChargeController::class, 'add'])
                ->name('quoteitemallowancecharge/add'),
            // Edit
            Route::methods([$mG, $mP], '/quoteitemallowancecharge/edit/{id}')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([QuoteItemAllowanceChargeController::class, 'edit'])
                ->name('quoteitemallowancecharge/edit'),
            Route::methods([$mG, $mP], '/quoteitemallowancecharge/delete/{id}')
                ->name('quoteitemallowancecharge/delete')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([QuoteItemAllowanceChargeController::class, 'delete']),
            Route::methods([$mG, $mP], '/quoteitemallowancecharge/view/{id}')
                ->name('quoteitemallowancecharge/view')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([QuoteItemAllowanceChargeController::class, 'view']),
            Route::get('/report')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([ReportController::class, 'index'])
                ->name('report/index'),
            Route::methods([$mG, $mP], '/sales_by_client_index')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([ReportController::class, 'sales_by_client_index'])
                ->name('report/sales_by_client_index'),
            Route::methods([$mG, $mP], '/sales_by_product_index')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([ReportController::class, 'sales_by_product_index'])
                ->name('report/sales_by_product_index'),
            Route::methods([$mG, $mP], '/sales_by_task_index')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([ReportController::class, 'sales_by_task_index'])
                ->name('report/sales_by_task_index'),
            Route::methods([$mG, $mP], '/sales_by_year_index')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([ReportController::class, 'sales_by_year_index'])
                ->name('report/sales_by_year_index'),
            Route::methods([$mG, $mP], '/invoice_aging_index')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([ReportController::class, 'invoice_aging_index'])
                ->name('report/invoice_aging_index'),
            Route::methods([$mG, $mP], '/payment_history_index')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([ReportController::class, 'payment_history_index'])
                ->name('report/payment_history_index'),
            Route::get('/salesorder[/page/{page:\d+}[/status/{status:\d+}]]')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([SalesOrderController::class, 'index'])
                ->name('salesorder/index'),
            Route::methods([$mG, $mP], '/sales_by_year')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([ReportController::class, 'sales_by_year'])
                ->name('report/sales_by_year'),
            Route::methods([$mG, $mP], '/salesorder/delete/{id}')
                ->name('salesorder/delete')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([SalesOrderController::class, 'delete']),
            Route::methods([$mG, $mP], '/salesorder/so_to_invoice/{id}')
                ->name('salesorder/so_to_invoice')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([SalesOrderController::class, 'so_to_invoice_confirm']),
            Route::methods([$mG, $mP], '/salesorder/so_to_invoice_confirm')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([SalesOrderController::class, 'so_to_invoice_confirm'])
                ->name('salesorder/so_to_invoice_confirm'),
            Route::get('/setting/debug_index[/page{page:\d+}]')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([SettingController::class, 'debug_index'])
                ->name('setting/debug_index'),
            Route::methods([$mG, $mP], '/setting/save')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([SettingController::class, 'save'])
                ->name('setting/save'),
            Route::methods([$mG, $mP], '/setting/tab_index[/{active:\d+}]')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([SettingController::class, 'tab_index'])
                ->name('setting/tab_index'),
            Route::methods([$mG, $mP], '/setting/add')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([SettingController::class, 'add'])
                ->name('setting/add'),
            Route::methods([$mG, $mP], '/setting/draft/{setting_id}')
                ->name('setting/draft')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([SettingController::class,
                    'inv_draft_has_number_switch']),
            Route::methods([$mG, $mP], '/setting/auto_client')
                ->name('setting/auto_client')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([SettingController::class, 'auto_client']),
            Route::methods([$mG, $mP], '/setting/mark_sent/{setting_id}')
                ->name('setting/mark_sent')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([SettingController::class, 'mark_sent']),
            Route::methods([$mG, $mP], '/setting/edit/{setting_id}')
                ->name('setting/edit')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([SettingController::class, 'edit']),
            Route::methods([$mG, $mP], '/setting/delete/{setting_id}')
                ->name('setting/delete')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([SettingController::class, 'delete']),
            Route::get('/setting/fphgenerate')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([SettingController::class, 'fphgenerate'])
                ->name('setting/fphgenerate'),
            Route::methods([$mG, $mP], '/setting/index')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([SettingController::class, 'index'])
                ->name('setting/index'),
            Route::methods([$mG, $mP], '/setting/get_cron_key')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([SettingController::class, 'get_cron_key'])
                ->name('setting/get_cron_key'),
            Route::methods([$mG, $mP], '/setting/view/{setting_id}')
                ->name('setting/view')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([SettingController::class, 'view']),
            Route::methods([$mG, $mP], '/setting/clear')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([SettingController::class, 'clear'])
                ->name('setting/clear'),
            Route::methods([$mG, $mP], '/setting/toggleinvsentlogcolumn')
                ->name('setting/toggleinvsentlogcolumn')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([SettingController::class,
                    'unhideOrHideToggleInvSentLogColumn']),
            Route::methods([$mG, $mP], '/setting/visible/{origin}')
                ->name('setting/visible')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([SettingController::class, 'visible']),
            Route::get('/task[/page/{page:\d+}]')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([TaskController::class, 'index'])
                ->name('task/index'),
            Route::methods([$mG, $mP], '/task/add')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([TaskController::class, 'add'])
                ->name('task/add'),
            Route::methods([$mG, $mP], '/task/selection_inv')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([TaskController::class, 'selection_inv'])
                ->name('task/selection_inv'),
            Route::methods([$mG, $mP], '/task/selection_quote')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([TaskController::class, 'selection_quote'])
                ->name('task/selection_quote'),
            Route::methods([$mG, $mP], '/task/edit/{id}')
                ->name('task/edit')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([TaskController::class, 'edit']),
            Route::methods([$mG, $mP], '/task/delete/{id}')
                ->name('task/delete')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([TaskController::class, 'delete']),
            Route::methods([$mG, $mP], '/task/view/{id}')
                ->name('task/view')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([TaskController::class, 'view']),
            Route::get('/taxrate')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([TaxRateController::class, 'index'])
                ->name('taxrate/index'),
            Route::methods([$mG, $mP], '/taxrate/add')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([TaxRateController::class, 'add'])
                ->name('taxrate/add'),
            Route::methods([$mG, $mP], '/taxrate/edit/{tax_rate_id}')
                ->name('taxrate/edit')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([TaxRateController::class, 'edit']),
            Route::methods([$mG, $mP], '/taxrate/delete/{tax_rate_id}')
                ->name('taxrate/delete')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([TaxRateController::class, 'delete']),
            Route::methods([$mG, $mP], '/taxrate/view/{tax_rate_id}')
                ->name('taxrate/view')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([TaxRateController::class, 'view']),
            Route::get('/telegram')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([TelegramController::class, 'index'])
                ->name('telegram/index'),
            Route::get('/telegram/delete_webhook')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([TelegramController::class, 'delete_webhook'])
                ->name('telegram/delete_webhook'),
            Route::get('/telegram/get_webhookinfo')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([TelegramController::class, 'get_webhookinfo'])
                ->name('telegram/get_webhookinfo'),
            Route::get('/telegram/set_webhook')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([TelegramController::class, 'set_webhook'])
                ->name('telegram/set_webhook'),
            Route::get('/telegram/get_updates')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([TelegramController::class, 'get_updates'])
                ->name('telegram/get_updates'),
            Route::methods([$mG, $mP], '/telegram/webhook')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([TelegramController::class, 'webhook'])
                ->name('telegram/webhook'),
            Route::get('/unit')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([UnitController::class, 'index'])
                ->name('unit/index'),
            Route::methods([$mG, $mP], '/unit/add')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([UnitController::class, 'add'])
                ->name('unit/add'),
            Route::methods([$mG, $mP], '/unit/edit/{unit_id}')
                ->name('unit/edit')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([UnitController::class, 'edit']),
            Route::methods([$mG, $mP], '/unit/delete/{unit_id}')
                ->name('unit/delete')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([UnitController::class, 'delete']),
            Route::methods([$mG, $mP], '/unit/view/{unit_id}')
                ->name('unit/view')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([UnitController::class, 'view']),
            // UnitPeppol
            Route::get('/unitpeppol')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([UnitPeppolController::class, 'index'])
                ->name('unitpeppol/index'),
            // Add
            Route::methods([$mG, $mP], '/unitpeppol/add')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([UnitPeppolController::class, 'add'])
                ->name('unitpeppol/add'),
            // Edit
            Route::methods([$mG, $mP], '/unitpeppol/edit/{id}')
                ->name('unitpeppol/edit')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([UnitPeppolController::class, 'edit']),
            Route::methods([$mG, $mP], '/unitpeppol/delete/{id}')
                ->name('unitpeppol/delete')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([UnitPeppolController::class, 'delete']),
            Route::methods([$mG, $mP], '/unitpeppol/view/{id}')
                ->name('unitpeppol/view')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([UnitPeppolController::class, 'view']),
            Route::get('/upload')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([UploadController::class, 'index'])
                ->name('upload/index'),
            Route::methods([$mG, $mP],
                    '/upload/add[/{origin}/{origin_id}/{action}]')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([UploadController::class, 'add'])
                ->name('upload/add'),
            Route::methods([$mG, $mP],
                    '/upload/edit/{id}[/{origin}/{origin_id}/{action}]')
                ->name('upload/edit')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([UploadController::class, 'edit']),
            Route::methods([$mG, $mP],
                    '/upload/delete/{id}[/{origin}/{origin_id}/{action}]')
                ->name('upload/delete')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([UploadController::class, 'delete']),
            Route::methods([$mG, $mP],
                    '/upload/view/{id}[/{origin}/{origin_id}/{action}]')
                ->name('upload/view')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([UploadController::class, 'view']),
            Route::methods([$mG, $mP], '/userclient/new/{user_id}')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([UserClientController::class, 'new'])
                ->name('userclient/new'),
            Route::methods([$mG, $mP], '/userclient/delete/{id}')
                ->name('userclient/delete')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([UserClientController::class, 'delete']),
            // UserInv
            Route::get('/userinv[/page/{page:\d+}[/active/{active}]]')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([UserInvController::class, 'index'])
                ->name('userinv/index'),
            // Add
            Route::methods([$mG, $mP], '/userinv/add')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([UserInvController::class, 'add'])
                ->name('userinv/add'),
            // Edit
            Route::methods([$mG, $mP], '/userinv/edit/{id}')
                ->name('userinv/edit')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([UserInvController::class, 'edit']),
            Route::methods([$mG, $mP], '/userinv/guest')
                ->name('userinv/guest')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEUI))
                ->action([UserInvController::class, 'guest']),
            Route::methods([$mG, $mP],
                    '/userinv/guestlimit/{userinv_id}/{limit}/{origin}')
                ->name('userinv/guestlimit')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEUI))
                ->action([UserInvController::class, 'guestlimit']),
            Route::methods([$mG, $mP], '/userinv/client/{id}')
                ->name('userinv/client')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([UserInvController::class, 'client']),
            Route::methods([$mG, $mP], '/userinv/delete/{id}')
                ->name('userinv/delete')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([UserInvController::class, 'delete']),
            Route::methods([$mG, $mP], '/userinv/view/{id}')
                ->name('userinv/view')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([UserInvController::class, 'view']),
            Route::methods([$mG, $mP], '/userinv/accountant/{user_id}')
                ->name('userinv/accountant')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([UserInvController::class, 'assignAccountantRole']),
            Route::methods([$mG, $mP], '/userinv/revoke/{user_id}')
                ->name('userinv/revoke')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([UserInvController::class, 'revokeAllRoles']),
            Route::methods([$mG, $mP], '/userinv/observer/{user_id}')
                ->name('userinv/observer')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([UserInvController::class, 'assignObserverRole']),
            Route::methods([$mG, $mP], '/userinv/admin/{user_id}')
                ->name('userinv/admin')
                ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
                ->action([UserInvController::class, 'assignAdminRole']),
/*
 * token e.g. maskedToken, tokenType e.g. email-verification, facebook-access,
 * github-access
 * Related logic: see AuthController function getTokenType($provider)
 */
            Route::methods([$mG, $mP],
                    '/userinv/signup/{language}/{token}/{tokenType}')
                ->name('userinv/signup')
                ->action([UserInvController::class, 'signup']),
        ), // invoice
];
