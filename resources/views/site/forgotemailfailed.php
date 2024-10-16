<?php

declare(strict_types=1);

use Yiisoft\Yii\Bootstrap5\Alert;
use Yiisoft\Yii\Bootstrap5\AlertType;

/** * 
 * @see \src\ViewInjection\CommonViewInjection.php
 * @var array $forgotemailfailed
 * 
 * @see 'i.password_reset_failed' .....
 * 'An error occurred while trying to send your password reset email. Please review the application logs or contact the system administrator.',
 */

$alert =  Alert::widget()
        ->addClass('shadow')
        ->type(AlertType::INFO)
        ->body((string)$forgotemailfailed['passwordResetFailed'].
               "\n".
               (string)$forgotemailfailed['invoiceEmailException']. ' Check your config/common/params.php mailer senderEmail configuration'.
               "\n", true)
        ->dismissable(true)
        ->render();
echo $alert;   