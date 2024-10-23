<?php

declare(strict_types=1);

use Stringable;
use Yiisoft\Yii\Bootstrap5\Alert;
use Yiisoft\Yii\Bootstrap5\AlertVariant;
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
                'danger' => AlertVariant::DANGER,
                'info' => AlertVariant::INFO,
                'primary' => AlertVariant::PRIMARY,
                'secondary' => AlertVariant::SECONDARY,
                'success' => AlertVariant::SUCCESS,
                'warning' => AlertVariant::WARNING,
                'light' => AlertVariant::LIGHT,
                'dark' => AlertVariant::DARK,
                'default' => AlertVariant::INFO
            };
            $alert = Alert::widget()
                     ->addClass('shadow')
                     ->variant($matchedKey)
                     // do not html encode since not user-generted code.
                     ->body($body, false)
                     ->dismissable(true)
                     ->render();
            echo $alert;
        }
    }
}
