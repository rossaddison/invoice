<?php

declare(strict_types=1);

use Yiisoft\Bootstrap5\Alert;
use Yiisoft\Bootstrap5\AlertVariant;

/** *
 * Related logic: see \src\ViewInjection\CommonViewInjection.php
 * @var array $adminmustmakeactive
 *
 * Related logic: see 'loginalert.user.inactive' .....
 * 'This user is marked as inactive. Please contact the system administrator.',
 */

$alert =  Alert::widget()
        ->addClass('shadow')
        ->variant(AlertVariant::INFO)
        ->body((string) $adminmustmakeactive['adminMustMakeActive'] .
               "\n", true)
        ->closeButtonAttributes(['class' => 'btn-lg'])
        ->dismissable(true)
        ->render();
echo $alert;
