<?php

declare(strict_types=1);

use Yiisoft\Html\Html;

use App\Widget\LabelSwitch;

/**
 * @var App\Invoice\Setting\SettingRepository $s
 * @var Yiisoft\Translator\TranslatorInterface $translator
 */ 

// Purpose: To remind the user that VAT is enabled
$s->getSetting('display_vat_enabled_message') === '1'
    ? LabelSwitch::checkbox(
        'quote-view-label-switch',
        $s->getSetting('enable_vat_registration'),
        $translator->translate('quote.label.switch.on'),
        $translator->translate('quote.label.switch.off'),
        'quote-view-label-switch-id',
        '16',
        ) : '';
?>
