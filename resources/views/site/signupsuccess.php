<?php

declare(strict_types=1);

use Yiisoft\Bootstrap5\Alert;
use Yiisoft\Bootstrap5\AlertVariant;

/**
 *
 * Related logic: see \src\ViewInjection\CommonViewInjection.php
 * @var array $signupsuccess
 *
 * 'i.email_successfully_sent'
 * 'Email successfully sent'
 */

$alert = Alert::widget()
        ->addClass('shadow')
        ->variant(AlertVariant::SUCCESS)
        ->body((string) $signupsuccess['emailSuccessfullySent'])
        ->dismissable(true)
        ->render();
echo $alert;
