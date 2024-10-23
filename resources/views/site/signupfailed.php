<?php

declare(strict_types=1);

use Yiisoft\Yii\Bootstrap5\Alert;
use Yiisoft\Yii\Bootstrap5\AlertVariant;

/**
 * 
 * @see \src\ViewInjection\CommonViewInjection.php
 * @var array $signupfailed 
 * 
 * @see 'invoice.invoice.email.not.sent.successfully'
 */

$alert = Alert::widget()
        ->addClass('shadow')
        ->variant(AlertVariant::DANGER)
        ->body((string)$signupfailed['emailNotSentSuccessfully']. ' config/common/params.php mailer senderEmail check'.
            "\n".
               (string)$signupfailed['invoiceEmailException']. 
            "\n")
        ->render();
echo $alert;