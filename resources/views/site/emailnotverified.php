<?php

declare(strict_types=1);

use Yiisoft\Bootstrap5\Alert;
use Yiisoft\Bootstrap5\AlertVariant;

/** *
 * Related logic: see \src\ViewInjection\CommonViewInjection.php
 * @var array $emailnotverified
 *
 * Related logic: see 'loginalert.user.emailnotverified' .....
 * 'Access Denied: Click on the verification link sent to your email address.'
 */

$alert =  Alert::widget()
        ->addClass('shadow')
        ->variant(AlertVariant::WARNING)
        ->body((string) $emailnotverified['emailNotVerified']
               . "\n", true)
        ->closeButtonAttributes(['class' => 'btn-lg'])
        ->dismissable(true)
        ->render();
echo $alert;
