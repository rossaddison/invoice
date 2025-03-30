<?php

declare(strict_types=1);

use Yiisoft\Bootstrap5\Alert;
use Yiisoft\Bootstrap5\AlertVariant;

/**
 *
 * @see \src\ViewInjection\CommonViewInjection.php
 * @var array $signupfailed
 *
 * @see 'invoice.invoice.email.not.sent.successfully'
 */

echo Alert::widget()
        ->addClass('shadow')
        ->variant(AlertVariant::DANGER)
        ->body((string)$signupfailed['emailNotSentSuccessfully']. ' config/common/params.php mailer senderEmail check'.
            "\n".
               (string)$signupfailed['invoiceEmailException'].
            "\n")
        ->dismissable(true)
        ->render();

echo Alert::widget()
        ->addClass('shadow')
        ->variant(AlertVariant::INFO)
        ->body((string)$signupfailed['localhostUserCanLoginAfterAdminMakesActive'])
        ->dismissable(true)
        ->render();
