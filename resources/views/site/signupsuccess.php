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
        ->addClass('shadow')
        ->type(AlertType::SUCCESS)
        ->body((string)$signupsuccess['emailSuccessfullySent'])
        ->dismissable(true)
        ->render();
echo $alert;