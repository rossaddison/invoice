<?php

declare(strict_types=1);

namespace App\Invoice\Inv\Widget;

use Yiisoft\Html\Html;
use Yiisoft\Translator\TranslatorInterface;

/**
 * The HomeCare worker's "install this app for offline access" nudge on
 * inv/guest. src/typescript/homecare-install-prompt.ts decides at runtime
 * whether to show it, by adding/removing `d-none` on the banner element and on
 * the message/button spans inside it.
 *
 * The flex layout therefore lives on an INNER wrapper, never on the banner
 * element itself: `d-flex` and `d-none` are both !important and `d-flex` wins,
 * which left an empty banner (just a close X) permanently on screen even where
 * the install prompt does not apply.
 */
final class HomeCareInstallBanner
{
    public const string ID = 'homecare-install-banner';

    public static function html(TranslatorInterface $translator): string
    {
        return Html::openTag('div', [
            'id' => self::ID,
            'class' => 'alert alert-info d-none mb-3',
            'role' => 'alert',
        ])
            . Html::openTag('div', ['class' => 'd-flex align-items-center justify-content-between'])
            . Html::openTag('div')
            . Html::tag('span', $translator->translate('homecare.offline.install.ios.message'), [
                'id' => 'homecare-install-message-ios', 'class' => 'd-none',
            ])
            . Html::tag('span', $translator->translate('homecare.offline.install.generic.message'), [
                'id' => 'homecare-install-message-generic', 'class' => 'd-none',
            ])
            . Html::closeTag('div')
            . Html::openTag('div', ['class' => 'd-flex align-items-center'])
            . Html::openTag('button', [
                'type' => 'button',
                'id' => 'homecare-install-button',
                'class' => 'btn btn-sm btn-primary ms-2 d-none',
            ])
            . Html::encode($translator->translate('homecare.offline.install.button'))
            . Html::closeTag('button')
            . Html::tag('button', '', [
                'type' => 'button',
                'id' => 'homecare-install-dismiss',
                'class' => 'btn-close ms-2',
                'aria-label' => $translator->translate('close'),
            ])
            . Html::closeTag('div')
            . Html::closeTag('div')
            . Html::closeTag('div');
    }
}
