<?php

declare(strict_types=1);

use Stringable;
use Yiisoft\Yii\Bootstrap5\Alert;

/**
 * @var Yiisoft\Session\Flash\Flash $flash
 */

?>

<?php

/**
 * @var array $flash->getAll()
 * @var array|string $value
 * @var string $key
 */
foreach ($flash->getAll() as $key => $value) {
  if (is_array($value)) {
    /**
     * @var Stringable|string $body
     */  
    foreach ($value as $key2 => $body) {
        $alert =  Alert::widget()
                ->body($body)
                ->options([
                    'class' => ['alert-'. $key .' shadow'],
                ])
                ->render();
        echo $alert;
    }
  }
}
