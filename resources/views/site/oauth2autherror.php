<?php

declare(strict_types=1);

use Yiisoft\Bootstrap5\Alert;
use Yiisoft\Bootstrap5\AlertVariant;

/**
 * @var string $message
 */

$alert = Alert::widget()
        ->addClass('shadow')
        ->variant(AlertVariant::WARNING)
        ->body($message, true)
        ->dismissable(true)
        ->render();
echo $alert;