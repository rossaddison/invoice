<?php

declare(strict_types=1);

use Yiisoft\Yii\Bootstrap5\Alert;
use Yiisoft\Yii\Bootstrap5\AlertType;

/**
 * 
 * @see \src\ViewInjection\CommonViewInjection.php
 * @var array $resetpasswordsuccess 
 * 
 * @see 'i.password_reset'
 * 'Password reset:'
 */

$alert = Alert::widget()
        ->addClass('shadow')
        ->type(AlertType::SUCCESS)
        ->body((string)$resetpasswordsuccess['resetPasswordSuccess'], true)
        ->dismissable(true)
        ->render();
echo $alert;