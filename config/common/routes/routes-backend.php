<?php

declare(strict_types=1);

use App\Backend\Controller\HmrcController;
use Psr\Http\Message\ResponseFactoryInterface;
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
    
    Route::get('/backend/hmrc')
        ->action([HmrcController::class, 'index'])
        ->name('backend/hmrc/index'),
    
    // Api specific feedback e.g. self-assessment, individuals, vat
    Route::get('backend/hmrc/fphFeedback/{api}')
        ->action([HmrcController::class, 'fphFeedback'])
        ->name('backend/hmrc/fphFeedback'),
    
    Route::methods([Method::GET, Method::POST], '/backend/hmrc/fphValidate')
        ->middleware(fn (
            ResponseFactoryInterface $responseFactory,
            StorageInterface $storage
        ) => new LimitRequestsMiddleware(new Counter($storage, 10, 10), $responseFactory))
        ->action([HmrcController::class, 'fphValidate'])
        ->name('backend/hmrc/fphValidate'),
    
    Route::get('/backend/hmrc/createTestUserIndividual')
        ->action([HmrcController::class, 'createTestUserIndividual'])
        ->name('backend/hmrc/createTestUserIndividual'),
];
