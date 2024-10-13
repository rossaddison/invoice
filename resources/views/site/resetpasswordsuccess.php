<?php

declare(strict_types=1);

use Yiisoft\Yii\Bootstrap5\Alert;

/**
 * 
 * @see \src\ViewInjection\CommonViewInjection.php
 * @var array $resetpasswordsuccess 
 * 
 * @see 'i.password_reset'
 * 'Password reset:'
 */

$alert = Alert::widget()
        ->body((string)$resetpasswordsuccess['resetPasswordSuccess'])
        ->options([
            'class' => ['alert-success shadow'],
        ])
        ->render();
echo $alert;