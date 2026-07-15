<?php

declare(strict_types=1);

use App\Auth\Permissions;
use App\Backend\Controller\HmrcController;
use App\Middleware\RoutePermission;
use Psr\Http\Message\ResponseFactoryInterface;
use Yiisoft\Auth\Middleware\Authentication;
use Yiisoft\Http\Method;
use Yiisoft\Router\Group;
use Yiisoft\Router\Route;
use Yiisoft\Yii\RateLimiter\Counter;
use Yiisoft\Yii\RateLimiter\LimitRequestsMiddleware;
use Yiisoft\Yii\RateLimiter\Storage\StorageInterface;

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
    // Authentication + permission gate, applied directly to this group.
    Group::create('/backend/hmrc')
        ->middleware(Authentication::class)
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
        ),
];
