<?php

declare(strict_types=1);

use Yiisoft\Bootstrap5\Alert;
use Yiisoft\Bootstrap5\AlertVariant;

/**
 * Related logic: see \src\ViewInjection\CommonViewInjection.php.
 *
 * @var array $resetpasswordsuccess
 *
 * Related logic: see 'i.password_reset'
 * 'Password reset:'
 */
$alert = Alert::widget()
    ->addClass('shadow')
    ->variant(AlertVariant::SUCCESS)
    ->body((string) $resetpasswordsuccess['resetPasswordSuccess'], true)
    ->dismissable(true)
    ->render();
echo $alert;
