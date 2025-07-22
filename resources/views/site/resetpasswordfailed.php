<?php

declare(strict_types=1);

use Yiisoft\Bootstrap5\Alert;
use Yiisoft\Bootstrap5\AlertVariant;

/**
 *
 * Related logic: see \src\ViewInjection\CommonViewInjection.php
 * @var array $resetpasswordfailed
 *
 * Related logic: see 'i.password_reset_failed'
 * 'An error occurred while trying to send your password reset email. Please review the application logs or contact the system administrator.'
 */

$alert = Alert::widget()
        ->addClass('shadow')
        ->variant(AlertVariant::WARNING)
        ->body((string) $resetpasswordfailed['resetPasswordFailed'], true)
        ->dismissable(true)
        ->render();
echo $alert;
