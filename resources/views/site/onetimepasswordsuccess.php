<?php

declare(strict_types=1);

use Yiisoft\Bootstrap5\Alert;
use Yiisoft\Bootstrap5\AlertVariant;

/**
 * @see \src\ViewInjection\CommonViewInjection.php
 * @var array $onetimepasswordsuccess
 */

$alert = Alert::widget()
        ->addClass('shadow')
        ->variant(AlertVariant::SUCCESS)
        ->body((string) $onetimepasswordsuccess['onetimePasswordSuccess'], true)
        ->dismissable(true)
        ->render();
echo $alert;
