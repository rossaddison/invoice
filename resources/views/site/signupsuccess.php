 <?php

declare(strict_types=1);

use Yiisoft\Yii\Bootstrap5\Alert;
use Yiisoft\Yii\Bootstrap5\AlertType;

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
        ->type(AlertType::SUCCESS)
        ->addClass('shadow')
        ->dismissable(true)
        ->render();
echo $alert;