<?php

declare(strict_types=1);

use Yiisoft\Bootstrap5\Alert;
use Yiisoft\Bootstrap5\AlertVariant;
use Yiisoft\Html\Html as H;
use Yiisoft\Translator\TranslatorInterface;

/**
 * Public, unauthenticated confirmation page for the HomeCare signup flow —
 * reached by clicking the emailed confirmation link. Rendered inside the
 * site's main layout, same as the generic signup success/failed pages
 * (resources/views/site/signupsuccess.php).
 *
 * @var string $outcome One of: 'paid', 'unpaid', 'expired', 'setup_incomplete'
 * @var string|null $qrDataUri Present for 'paid'/'unpaid' — the client's QR code image
 * @var TranslatorInterface $translator
 * @var Yiisoft\View\WebView $this
 */

$this->setTitle($translator->translate('homecare.signup.title'));

$messageKey = match ($outcome) {
    'paid' => 'homecare.signup.confirmed.paid',
    'unpaid' => 'homecare.signup.confirmed.unpaid',
    'expired' => 'homecare.signup.confirmed.expired',
    default => 'homecare.signup.confirmed.setup.incomplete',
};

$variant = match ($outcome) {
    'paid', 'unpaid' => AlertVariant::SUCCESS,
    'expired' => AlertVariant::WARNING,
    default => AlertVariant::DANGER,
};

echo Alert::widget()
    ->addClass('shadow')
    ->variant($variant)
    ->body($translator->translate($messageKey))
    ->dismissable(false)
    ->render();

if ($qrDataUri !== null) {
    echo H::img($qrDataUri)
        ->alt($translator->translate('print.qr.code'))
        ->addClass('mt-3')
        ->addAttributes(['style' => 'max-width: 220px;'])
        ->render();
}
