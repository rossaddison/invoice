<?php

declare(strict_types=1);

use App\Auth\Permissions;
use App\Backend\Controller\HmrcController;
use App\Middleware\RoutePermission;
use Psr\Http\Message\ResponseFactoryInterface;
use Yiisoft\Http\Method;
use Yiisoft\Router\Group;
use Yiisoft\Router\Route;
use Yiisoft\Yii\RateLimiter\Counter;
use Yiisoft\Yii\RateLimiter\LimitRequestsMiddleware;
use Yiisoft\Yii\RateLimiter\Storage\StorageInterface;

// The eight routes for the "Income Received" replacement APIs (see
// HmrcApiCatalogue::all()'s own comment for why) built from a loop
// over their category names rather than eight repeated
// Route::get($path)->action($action)->name($name) statements --
// SonarCloud's own new_duplicated_lines_density flagged exactly that
// shape as real duplication once eight of them sat back-to-back below
// (this file already repeats that chain across a dozen pre-existing,
// genuinely varied routes above; eight uniform ones in a row is what
// crossed the threshold). Every category name maps predictably onto
// its path (/income{Category}), controller action (income{Category}),
// and route name (backend/hmrc/income{Category}) -- HmrcController's
// own INCOME_CATEGORIES array keys them by a lowercase-hyphenated slug
// instead, unrelated to this naming, since routing and the HMRC HTTP
// request shape are independent concerns.
$incomeRoutes = [];
foreach (
    ['Dividends', 'Employments', 'Foreign', 'InsurancePolicies',
        'Other', 'Partner', 'Pensions', 'Savings'] as $incomeCategory
) {
    $incomeRoutes[] = Route::get('/income' . $incomeCategory)
        ->action([HmrcController::class, 'income' . $incomeCategory])
        ->name('backend/hmrc/income' . $incomeCategory);
}

return [
    Group::create('')
        ->routes(
            Route::get('/')
                ->action([HmrcController::class, 'index'])
                ->name('index'),
        )
        ->host('backend.{_host}')
        ->namePrefix('backend/'),

    // Admin-only: VAT filing and other HMRC MTD actions. Not under
    // RoutePermission::invoiceGroup() since these intentionally stay at
    // /backend/hmrc/* rather than gaining an /invoice prefix — same
    // permission gate, applied directly to this group.
    //
    // Yiisoft\Auth\Middleware\Authentication used to sit alongside this
    // middleware, added in the same July 2026 security-hardening pass —
    // removed because it requires a Yiisoft\Auth\AuthenticatorInterface
    // DI binding that has never existed anywhere in this app (confirmed:
    // no implementation, no config binding, no other route uses it
    // either). That made this middleware unconstructable — every request
    // to /backend/hmrc/* has 500'd with a DI NotFoundException since the
    // day it was added, regardless of yiisoft/auth version. This
    // RoutePermission::check() call is the same, real, working RBAC gate
    // every other protected route in this app already relies on alone.
    Group::create('/backend/hmrc')
        ->middleware(RoutePermission::check(Permissions::MANAGE_HMRC))
        ->routes(
            Route::get('')
                ->action([HmrcController::class, 'index'])
                ->name('backend/hmrc/index'),

            // Api specific feedback e.g. self-assessment, individuals, vat
            Route::get('/fphFeedback/{api}')
                ->action([HmrcController::class, 'fphFeedback'])
                ->name('backend/hmrc/fphFeedback'),
            Route::methods([Method::GET, Method::POST], '/fphValidate')
                ->middleware(fn (
                    ResponseFactoryInterface $responseFactory,
                    StorageInterface $storage,
                ) => new LimitRequestsMiddleware(new Counter($storage, 10, 10), $responseFactory))
                ->action([HmrcController::class, 'fphValidate'])
                ->name('backend/hmrc/fphValidate'),
            Route::get('/createTestUserIndividual')
                ->action([HmrcController::class, 'createTestUserIndividual'])
                ->name('backend/hmrc/createTestUserIndividual'),

            // https://developer.service.hmrc.gov.uk/api-documentation/docs/api/service/vat-api/1.0
            Route::get('/vatReturnPrepare')
                ->action([HmrcController::class, 'vatReturnPrepare'])
                ->name('backend/hmrc/vatReturnPrepare'),
            Route::get('/vatObligations')
                ->action([HmrcController::class, 'vatObligations'])
                ->name('backend/hmrc/vatObligations'),
            Route::methods([Method::GET, Method::POST], '/vatReturnSubmit')
                ->action([HmrcController::class, 'vatReturnSubmit'])
                ->name('backend/hmrc/vatReturnSubmit'),

            // https://developer.service.hmrc.gov.uk/api-documentation/docs/api/service/self-employment-business-api/3.0
            Route::get('/selfEmploymentBusinesses')
                ->action([HmrcController::class, 'selfEmploymentBusinesses'])
                ->name('backend/hmrc/selfEmploymentBusinesses'),

            // https://developer.service.hmrc.gov.uk/api-documentation/docs/api/service/obligations-api/3.0
            Route::get('/incomeTaxObligations')
                ->action([HmrcController::class, 'incomeTaxObligations'])
                ->name('backend/hmrc/incomeTaxObligations'),

            // https://developer.service.hmrc.gov.uk/api-documentation/docs/api/service/self-assessment-api/3.0
            Route::get('/itsaStatus')
                ->action([HmrcController::class, 'itsaStatus'])
                ->name('backend/hmrc/itsaStatus'),

            // https://developer.service.hmrc.gov.uk/api-documentation/docs/api/service/individual-calculations-api/8.0
            Route::methods([Method::GET, Method::POST], '/individualCalculations')
                ->action([HmrcController::class, 'individualCalculations'])
                ->name('backend/hmrc/individualCalculations'),

            // The eight replacements for the deprecated "Income
            // Received" API -- see HmrcApiCatalogue::all()'s own
            // comment, and $incomeRoutes's own comment above.
            ...$incomeRoutes,
        ),
];
