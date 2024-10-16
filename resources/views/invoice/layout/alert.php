<?php

declare(strict_types=1);

use Stringable;
use Yiisoft\Yii\Bootstrap5\Alert;
use Yiisoft\Yii\Bootstrap5\AlertType;
use Yiisoft\Html\Tag\Body;

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
            $matchedKey = match ($key) {
                'danger' => AlertType::DANGER,
                'info' => AlertType::INFO,
                'primary' => AlertType::PRIMARY,
                'secondary' => AlertType::SECONDARY,
                'success' => AlertType::SUCCESS,
                'warning' => AlertType::WARNING,
                'default' => AlertType::INFO
            };
            $alert = Alert::widget()
                     ->addClass('shadow')
                     ->type($matchedKey)
                     // do not html encode since not user-generted code.
                     ->body($body, false)
                     ->dismissable(true)
                     ->render();
            echo $alert;
        }
    }
}
