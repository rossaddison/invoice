<?php

declare(strict_types=1);

/**
 * Related logic: see GeneratorController function _route
 * @var App\Invoice\Entity\Gentor $generator
 *
 */

echo "<?php\n";
?>

use App\Auth\Permissions;
use App\Invoice\<?= $generator->getCamelcaseCapitalName(); ?>\<?= $generator->getCamelcaseCapitalName() . 'Controller;'; ?>

    Route::get('/<?= $generator->getRouteSuffix(); ?>[/page/{page:\d+}]')
    ->middleware(fn (AccessChecker $checker) => $checker->withPermission(Permissions::EDIT_INV))
    ->middleware(Authentication::class)
    ->action([<?= $generator->getCamelcaseCapitalName(); ?>Controller::class, 'index'])
    ->name('<?= $generator->getRouteSuffix(); ?>/index'),    
    // Add
    Route::methods([Method::GET, Method::POST], '/<?= $generator->getRouteSuffix(); ?>/add')
    ->middleware(fn (AccessChecker $checker) => $checker->withPermission(Permissions::EDIT_INV))
    ->middleware(Authentication::class)
    ->action([<?= $generator->getCamelcaseCapitalName(); ?>Controller::class, 'add'])
    ->name('<?= $generator->getRouteSuffix(); ?>/add'),
    // Edit 
    Route::methods([Method::GET, Method::POST], '/<?= $generator->getRouteSuffix(); ?>/edit/{id}')
    ->middleware(fn (AccessChecker $checker) => $checker->withPermission(Permissions::EDIT_INV))
    ->middleware(Authentication::class)
    ->action([<?= $generator->getCamelcaseCapitalName(); ?>Controller::class, 'edit'])
    ->name('<?= $generator->getRouteSuffix(); ?>/edit'), 
    Route::methods([Method::GET, Method::POST], '/<?= $generator->getRouteSuffix(); ?>/delete/{id}')
    ->middleware(fn (AccessChecker $checker) => $checker->withPermission(Permissions::EDIT_INV))
    ->middleware(Authentication::class)
    ->action([<?= $generator->getCamelcaseCapitalName(); ?>Controller::class, 'delete'])
    ->name('<?= $generator->getRouteSuffix(); ?>/delete'),
    Route::methods([Method::GET, Method::POST], '/<?= $generator->getRouteSuffix(); ?>/view/{id}')
    ->middleware(fn (AccessChecker $checker) => $checker->withPermission(Permissions::EDIT_INV))
    ->middleware(Authentication::class)
    ->action([<?= $generator->getCamelcaseCapitalName(); ?>Controller::class, 'view'])
    ->name('<?= $generator->getRouteSuffix(); ?>/view'),    
<?php
     echo "?>";
?>        