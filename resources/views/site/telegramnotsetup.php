<?php

declare(strict_types=1);

use Yiisoft\Bootstrap5\Alert;
use Yiisoft\Bootstrap5\AlertVariant;

/**
 * Related logic: see \src\ViewInjection\CommonViewInjection.php.
 *
 * @var array $telegramnotsetup
 */
$alert = Alert::widget()
    ->addClass('shadow')
    ->variant(AlertVariant::WARNING)
    ->body((string) $telegramnotsetup['telegramNotSetup'], true)
    ->dismissable(true)
    ->render();
echo $alert;
