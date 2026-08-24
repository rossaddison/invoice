<?php

declare(strict_types=1);

use App\Invoice\Enum\FlashScope;
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

// This is the staff/guest-portal reader — the storefront layout
// (resources/views/layout/templates/storefront/main.php) reads its own,
// separately FlashScope::Shop-prefixed keys. Enumerating FlashScope's
// fixed, known level names and reading each with get() (rather than
// getAll()) means this reader only ever touches — and only ever expires —
// its own unscoped keys, never a shop-scoped message meant for the other
// layout. See FlashScope's own docblock.
/** @var array<string, array|string> $flashMessages */
$flashMessages = [];
foreach (FlashScope::levels() as $level) {
    /** @var array|string|null $value */
    $value = $flash->get($level);
    if (null !== $value) {
        $flashMessages[$level] = $value;
    }
}

// Debug: Check if there are any flash messages
if (empty($flashMessages)) {
    // Uncomment the line below to test if alerts are working
    // echo '<div class="alert alert-info alert-dismissible fade show" role="alert">Test message - no flash messages found <button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
}
?>

<!-- Flash Messages CSS and JavaScript -->
<style>
.flash-message-container {
    position: relative;
    margin-bottom: 1rem;
}

.countdown-timer {
    position: absolute;
    top: -3px;
    right: -3px;
    width: 24px;
    height: 24px;
    border-radius: 50%;
    background: rgba(0, 0, 0, 0.7);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 10px;
    font-weight: bold;
    z-index: 1051;
    border: 2px solid white;
    box-shadow: 0 2px 4px rgba(0,0,0,0.2);
    cursor: pointer;
    transition: background-color 0.2s ease;
}

.countdown-timer:hover {
    background: rgba(0, 0, 0, 0.9);
}

.countdown-timer.paused {
    background: rgba(255, 165, 0, 0.8);
}

.countdown-timer.paused:hover {
    background: rgba(255, 165, 0, 1);
}

.pause-button {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    font-size: 8px;
    z-index: 1;
}

.countdown-progress {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    border-radius: 50%;
    background: conic-gradient(from 0deg, #007bff 0%, #007bff var(--progress), transparent var(--progress), transparent 100%);
    opacity: 0.8;
}

.flash-message-fade {
    transition: opacity 0.5s ease-in-out, transform 0.3s ease-in-out;
    display: block !important;
    opacity: 1 !important;
}

.flash-message-fade.hiding {
    opacity: 0;
    transform: translateX(100%);
}
</style>

<?php
// FlashMessageTimer moved to src/typescript/flash-message-timer.ts (bundled
// into invoice-typescript-iife.js) so script-src no longer needs
// 'unsafe-inline'. It self-initializes unconditionally from index.ts.

foreach ($flashMessages as $key => $value) {
    if (is_array($value)) {
        /**
         * @var Stringable|string $body
         */
        foreach ($value as $body) {
            $matchedKey = match ($key) {
                'danger' => $danger,
                'info' => $info,
                'primary' => $primary,
                'secondary' => $secondary,
                'success' => $success,
                'warning' => $warning,
                'light' => $light,
                'dark' => $dark,
                'default' => $info,
            };

            $alert = Alert::widget()
                     ->addCssStyle([
                         'font-size' => $alertMessageFontSize . 'px',
                         'font-family' =>  $alertMessageFont,
                     ])
                     ->addClass('btn-flash-message-close flash-message-fade')
                     ->closeButtonTag('button')
                     ->closeButtonAttributes(['style' => 'font-size:' . $alertCloseButtonFontSize . 'px'])
                     ->variant($matchedKey)
                     // do not html encode since not user-generated code.
                     ->body($body, false)
                     ->dismissable(true)
                     ->render();
            echo $alert;
        }
    }
}
