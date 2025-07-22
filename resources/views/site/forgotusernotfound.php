<?php

declare(strict_types=1);

use Yiisoft\Bootstrap5\Alert;
use Yiisoft\Bootstrap5\AlertVariant;

/**
 * @see \src\ViewInjection\CommonViewInjection.php
 *
 * @var array $forgotusernotfound
 *
 * @see 'i.loginalert_user_not_found' .....
 * 'There is no account registered with this Email address.'
 */
$alert = Alert::widget()
    ->addClass('shadow')
    ->variant(AlertVariant::WARNING)
    ->body((string) $forgotusernotfound['loginAlertUserNotFound'], true)
    ->dismissable(true)
    ->render();
echo $alert;
