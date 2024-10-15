<?php

declare(strict_types=1);

use Yiisoft\Yii\Bootstrap5\Alert;
use Yiisoft\Yii\Bootstrap5\AlertType;

/**
 * @see \src\ViewInjection\CommonViewInjection.php
 * @var array $forgotusernotfound
 * 
 * @see 'i.loginalert_user_not_found' .....
 * 'There is no account registered with this Email address.'
 */

$alert = Alert::widget()
        ->body((string)$forgotusernotfound['loginAlertUserNotFound'], true)
        ->type(AlertType::WARNING)
        ->addClass('shadow')
        ->dismissable(true)
        ->render();
echo $alert;