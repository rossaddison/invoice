<?php

declare(strict_types=1);

use Yiisoft\Yii\Bootstrap5\Alert;
use Yiisoft\Yii\Bootstrap5\AlertType;

/**
 * @see \src\ViewInjection\CommonViewInjection.php
 * @var array $forgotalert
 *
 * 'i.password_reset_email'
 * 'You requested a new password for your installation. Please click the link to reset your password:',
 */

$alert =  Alert::widget()
        ->body((string)$forgotalert['passwordResetEmail'], true)
        ->type(AlertType::INFO)
        ->addClass('shadow')
        ->render();
echo $alert;