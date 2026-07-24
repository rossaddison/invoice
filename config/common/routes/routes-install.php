<?php

declare(strict_types=1);

use App\Install\Controller\InstallController;
use Yiisoft\Http\Method;
use Yiisoft\Router\Route;

$mG = Method::GET;
$mP = Method::POST;

/**
 * Web-based install wizard: guest-accessible like /signup and /login. No
 * auth middleware — protection is the self-lock guard inside
 * InstallController (an already-installed system redirects every action
 * straight to /login), not route-level authorization.
 */
return [
    Route::get('/install')
        ->action([InstallController::class, 'index'])
        ->name('install/index'),
    Route::methods([$mG, $mP], '/install/test-connection')
        ->action([InstallController::class, 'testConnection'])
        ->name('install/testConnection'),
    Route::post('/install/database')
        ->action([InstallController::class, 'saveDatabase'])
        ->name('install/database'),
    Route::post('/install/build-tables')
        ->action([InstallController::class, 'buildTables'])
        ->name('install/buildTables'),
];
