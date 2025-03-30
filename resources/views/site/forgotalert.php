<?php

declare(strict_types=1);

use Yiisoft\Bootstrap5\Alert;
use Yiisoft\Bootstrap5\AlertVariant;

/**
 * @see \src\ViewInjection\CommonViewInjection.php
 * @var array $forgotalert
 *
 * 'i.password_reset_email'
 * 'You requested a new password for your installation. Please click the link to reset your password:',
 */

$alert =  Alert::widget()
        ->addClass('shadow')
        ->variant(AlertVariant::INFO)
        ->body((string)$forgotalert['passwordResetEmail'], true)
        ->dismissable(true)
        ->render();
echo $alert;
