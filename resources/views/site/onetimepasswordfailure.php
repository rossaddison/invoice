<?php

declare(strict_types=1);

use Yiisoft\Bootstrap5\Alert;
use Yiisoft\Bootstrap5\AlertVariant;

/**
 * Related logic: see \src\ViewInjection\CommonViewInjection.php.
 *
 * @var array $onetimepasswordfailure
 */
$alert = Alert::widget()
    ->addClass('shadow')
    ->variant(AlertVariant::DANGER)
    ->body((string) $onetimepasswordfailure['onetimePasswordFailure'], true)
    ->dismissable(true)
    ->render();
echo $alert;
