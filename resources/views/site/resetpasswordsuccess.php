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
        ->body((string)$resetpasswordsuccess['resetPasswordSuccess'], true)
        ->type(AlertType::SUCCESS)
        ->addClass('shadow')
        ->render();
echo $alert;