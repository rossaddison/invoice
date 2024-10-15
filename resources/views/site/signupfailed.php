<?php

declare(strict_types=1);

use Yiisoft\Yii\Bootstrap5\Alert;
use Yiisoft\Yii\Bootstrap5\AlertType;

/**
 * 
 * @see \src\ViewInjection\CommonViewInjection.php
 * @var array $signupfailed 
 * 
 * @see 'invoice.invoice.email.not.sent.successfully'
 */

$alert = Alert::widget()
        ->body((string)$signupfailed['emailNotSentSuccessfully']. ' config/common/params.php mailer senderEmail check'.
            "\n".
               (string)$signupfailed['invoiceEmailException']. 
            "\n")
        ->type(AlertType::DANGER)
        ->addClass('shadow')
        ->render();
echo $alert;