<?php

use Yiisoft\Html\Html;
use Yiisoft\Translator\Translator;

/**
 * @var Translator $translator
 * @var string $alert
 * @var string $title
 * @var float $balance
 * @var float $total
 * @var string $authUrl
 * @var string $client_chosen_gateway
 * @var array|object $client_on_invoice
 * @var object|array $invoice
 * @var string $inv_url_key
 * @var bool $is_overdue
 * @var bool $disable_form
 * @var string $companyLogo
 * @var string $partial_client_address
 * @var string $payment_method
 * @var string $provider
 * @var string $json_encoded_items
 */

echo $alert;
echo $companyLogo;

echo Html::tag('h2', Html::encode($title));

echo Html::tag('p', Html::encode($translator->translate('amount.payment')) . ': ' . Html::encode(number_format($balance, 2)));
echo Html::tag('p', Html::encode($translator->translate('total')) . ': ' . Html::encode(number_format($total, 2)));

$clientName = '';
if (is_object($client_on_invoice) && method_exists($client_on_invoice, 'getClient_name')) {
    /** @var string|null $maybeName */
    $maybeName = $client_on_invoice->getClient_name();
    if (is_string($maybeName)) {
        $clientName = $maybeName;
    }
} elseif (is_array($client_on_invoice) && isset($client_on_invoice['client_name']) && is_string($client_on_invoice['client_name'])) {
    $clientName = $client_on_invoice['client_name'];
}
echo Html::tag('p', Html::encode($translator->translate('client')) . ': ' . Html::encode($clientName));
echo $partial_client_address;

echo Html::tag('p', Html::encode($translator->translate('payment.method')) . ': ' . Html::encode($payment_method) . $provider);

echo Html::tag(
    'p',
    Html::encode($translator->translate('invoice')) . ': ' . Html::encode($inv_url_key)
);

if ($is_overdue) {
    echo Html::tag(
        'div',
        Html::encode($translator->translate('invoice.is.overdue')),
        ['class' => 'alert alert-warning']
    );
}

// Disabled form notice
if ($disable_form) {
    echo Html::tag(
        'div',
        Html::encode($translator->translate('form.disabled.already.paid')),
        ['class' => 'alert alert-info']
    );
}

if (!empty($authUrl) && !$disable_form) {
    echo Html::a(
        Html::encode($translator->translate('open.banking.pay.with'). $provider),
        $authUrl,
        [
            'class' => 'btn btn-primary',
            'rel' => 'noopener noreferrer',
            'target' => '_blank',
        ]
    );
} elseif ($disable_form) {
    echo Html::tag(
        'p',
        Html::encode($translator->translate('open.banking.payment.not.required'))
    );
} else {
    echo Html::tag(
        'p',
        Html::encode($translator->translate('open.banking.not.configured'))
    );
}