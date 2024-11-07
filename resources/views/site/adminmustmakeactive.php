<?php

declare(strict_types=1);

use Yiisoft\Yii\Bootstrap5\Alert;
use Yiisoft\Yii\Bootstrap5\AlertVariant;

/** * 
 * @see \src\ViewInjection\CommonViewInjection.php
 * @var array $adminmustmakeactive
 * 
 * @see 'i.loginalert_user_inactive' .....
 * 'This user is marked as inactive. Please contact the system administrator.',
 */

$alert =  Alert::widget()
        ->addClass('shadow')
        ->variant(AlertVariant::INFO)
        ->body((string)$adminmustmakeactive['adminMustMakeActive'].
               "\n", true)
        ->closeButtonAttributes(['class' => 'btn-lg'])
        ->dismissable(true)
        ->render();
echo $alert;   