<?php

declare(strict_types=1);

/**
 * @see GeneratorController function _route
 *
 * @var App\Invoice\Entity\Gentor $generator
 */
echo "<?php\n";
?>

use App\Invoice\<?php echo $generator->getCamelcase_capital_name(); ?>\<?php echo $generator->getCamelcase_capital_name().'Controller;'; ?>

    Route::get('/<?php echo $generator->getRoute_suffix(); ?>[/page/{page:\d+}]')
    ->middleware(fn (AccessChecker $checker) => $checker->withPermission('editInv'))
    ->middleware(Authentication::class)
    ->action([<?php echo $generator->getCamelcase_capital_name(); ?>Controller::class, 'index'])
    ->name('<?php echo $generator->getRoute_suffix(); ?>/index'),    
    // Add
    Route::methods([Method::GET, Method::POST], '/<?php echo $generator->getRoute_suffix(); ?>/add')
    ->middleware(fn (AccessChecker $checker) => $checker->withPermission('editInv'))
    ->middleware(Authentication::class)
    ->action([<?php echo $generator->getCamelcase_capital_name(); ?>Controller::class, 'add'])
    ->name('<?php echo $generator->getRoute_suffix(); ?>/add'),
    // Edit 
    Route::methods([Method::GET, Method::POST], '/<?php echo $generator->getRoute_suffix(); ?>/edit/{id}')
    ->middleware(fn (AccessChecker $checker) => $checker->withPermission('editInv'))
    ->middleware(Authentication::class)
    ->action([<?php echo $generator->getCamelcase_capital_name(); ?>Controller::class, 'edit'])
    ->name('<?php echo $generator->getRoute_suffix(); ?>/edit'), 
    Route::methods([Method::GET, Method::POST], '/<?php echo $generator->getRoute_suffix(); ?>/delete/{id}')
    ->middleware(fn (AccessChecker $checker) => $checker->withPermission('editInv'))
    ->middleware(Authentication::class)
    ->action([<?php echo $generator->getCamelcase_capital_name(); ?>Controller::class, 'delete'])
    ->name('<?php echo $generator->getRoute_suffix(); ?>/delete'),
    Route::methods([Method::GET, Method::POST], '/<?php echo $generator->getRoute_suffix(); ?>/view/{id}')
    ->middleware(fn (AccessChecker $checker) => $checker->withPermission('editInv'))
    ->middleware(Authentication::class)
    ->action([<?php echo $generator->getCamelcase_capital_name(); ?>Controller::class, 'view'])
    ->name('<?php echo $generator->getRoute_suffix(); ?>/view'),    
<?php
     echo '?>';
?>        