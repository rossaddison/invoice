<?php

declare(strict_types=1);

/**
 * Related logic: see GeneratorController function _route
 * @var App\Invoice\Entity\Gentor $generator
 *
 */

echo "<?php\n";
?>

use App\Invoice\<?= $generator->getCamelcase_capital_name(); ?>\<?= $generator->getCamelcase_capital_name() . 'Controller;'; ?>

    Route::get('/<?= $generator->getRoute_suffix(); ?>[/page/{page:\d+}]')
    ->middleware(fn (AccessChecker $checker) => $checker->withPermission('editInv'))
    ->middleware(Authentication::class)
    ->action([<?= $generator->getCamelcase_capital_name(); ?>Controller::class, 'index'])
    ->name('<?= $generator->getRoute_suffix(); ?>/index'),    
    // Add
    Route::methods([Method::GET, Method::POST], '/<?= $generator->getRoute_suffix(); ?>/add')
    ->middleware(fn (AccessChecker $checker) => $checker->withPermission('editInv'))
    ->middleware(Authentication::class)
    ->action([<?= $generator->getCamelcase_capital_name(); ?>Controller::class, 'add'])
    ->name('<?= $generator->getRoute_suffix(); ?>/add'),
    // Edit 
    Route::methods([Method::GET, Method::POST], '/<?= $generator->getRoute_suffix(); ?>/edit/{id}')
    ->middleware(fn (AccessChecker $checker) => $checker->withPermission('editInv'))
    ->middleware(Authentication::class)
    ->action([<?= $generator->getCamelcase_capital_name(); ?>Controller::class, 'edit'])
    ->name('<?= $generator->getRoute_suffix(); ?>/edit'), 
    Route::methods([Method::GET, Method::POST], '/<?= $generator->getRoute_suffix(); ?>/delete/{id}')
    ->middleware(fn (AccessChecker $checker) => $checker->withPermission('editInv'))
    ->middleware(Authentication::class)
    ->action([<?= $generator->getCamelcase_capital_name(); ?>Controller::class, 'delete'])
    ->name('<?= $generator->getRoute_suffix(); ?>/delete'),
    Route::methods([Method::GET, Method::POST], '/<?= $generator->getRoute_suffix(); ?>/view/{id}')
    ->middleware(fn (AccessChecker $checker) => $checker->withPermission('editInv'))
    ->middleware(Authentication::class)
    ->action([<?= $generator->getCamelcase_capital_name(); ?>Controller::class, 'view'])
    ->name('<?= $generator->getRoute_suffix(); ?>/view'),    
<?php
     echo "?>";
?>        