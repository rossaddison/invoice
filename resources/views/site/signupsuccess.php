 <?php

declare(strict_types=1);

use Yiisoft\Yii\Bootstrap5\Alert;

/**
 * 
 * @see \src\ViewInjection\CommonViewInjection.php
 * @var array $signupsuccess 
 * 
 * 'i.email_successfully_sent'
 * 'Email successfully sent'
 */

$alert = Alert::widget()
        ->body((string)$signupsuccess['emailSuccessfullySent'])
        ->options([
            'class' => ['alert-success shadow'],
        ])
        ->render();
echo $alert;