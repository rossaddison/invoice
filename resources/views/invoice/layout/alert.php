<?php
declare(strict_types=1); 

use Yiisoft\Yii\Bootstrap5\Alert;

foreach ($flash->getAll() as $key => $value) {
  if (is_array($value)) {
    foreach ($value as $key2 => $value2) {
      $alert =  Alert::widget()
              ->body($value2)
              ->options([
                  'class' => ['alert-'. $key .' shadow'],
              ])
              ->render();
      echo $alert;
    }
  }
}
