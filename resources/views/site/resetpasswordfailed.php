<?php

declare(strict_types=1);

use Yiisoft\Yii\Bootstrap5\Alert;
use Yiisoft\Yii\Bootstrap5\AlertType;

/**
 * 
 * @see \src\ViewInjection\CommonViewInjection.php
 * @var array $resetpasswordfailed
 * 
 * @see 'i.password_reset_failed'
 * 'An error occurred while trying to send your password reset email. Please review the application logs or contact the system administrator.'
 */

$alert = Alert::widget()
        ->body((string)$resetpasswordfailed['resetPasswordFailed'], true)
        ->type(AlertType::WARNING)
        ->addClass('shadow')
        ->render();
echo $alert;
