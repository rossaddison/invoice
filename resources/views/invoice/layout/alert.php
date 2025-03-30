<?php

declare(strict_types=1);

use Stringable;
use Yiisoft\Bootstrap5\Alert;
use Yiisoft\Bootstrap5\AlertVariant;

/**
 * @var Yiisoft\Session\Flash\Flash $flash
 * @var App\Invoice\Setting\SettingRepository $s
 */

?>

<?php

$alertMessageFont = $s->getSetting('bootstrap5_alert_message_font') ?: 'Arial';
$alertMessageFontSize = $s->getSetting('bootstrap5_alert_message_font_size') ?: '16';
$alertCloseButtonFontSize = $s->getSetting('bootstrap5_alert_close_button_font_size') ?: '10';

$danger =  AlertVariant::DANGER;
$info = AlertVariant::INFO;
$primary =  AlertVariant::PRIMARY;
$secondary = AlertVariant::SECONDARY;
$success = AlertVariant::SUCCESS;
$warning = AlertVariant::WARNING;
$light = AlertVariant::LIGHT;
$dark = AlertVariant::DARK;

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
                'danger' => $danger,
                'info' => $info,
                'primary' => $primary,
                'secondary' => $secondary,
                'success' => $success,
                'warning' => $warning,
                'light' => $light,
                'dark' => $dark,
                'default' => $info
            };
            $alert = Alert::widget()
                     ->addClass('shadow')
                     ->addCssStyle([
                         'font-size' => $alertMessageFontSize . 'px',
                         'font-family' =>  $alertMessageFont,
                     ])
                     ->addClass('btn-flash-message-close')
                     ->closeButtonTag('button')
                     ->closeButtonAttributes(['style' => 'font-size:'. $alertCloseButtonFontSize. 'px'])
                     ->variant($matchedKey)
                     // do not html encode since not user-generted code.
                     ->body($body, false)
                     ->dismissable(true)
                     ->render();
            echo $alert;
        }
    }
}
