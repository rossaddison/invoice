<?php

use Yiisoft\Html\Html;
use Yiisoft\Translator\Translator;

/**
 * @var Translator $translator
 * @var string $authUrl
 * @var string $alert
 * @var float $balance
 * @var string $title
 */

echo $alert;

echo Html::tag('h2', Html::encode($title));

echo Html::tag(
    'p',
    Html::encode($translator->translate('amount')) . ': ' . Html::encode((string)$balance)
);

if (!empty($authUrl)) {
    echo Html::a(
        $translator->translate('open.banking.pay.with'),
        $authUrl,
        [
            'class' => 'btn btn-primary',
            'rel' => 'noopener noreferrer',
            'target' => '_blank',
        ]
    );
} else {
    echo Html::tag(
        'p',
        $translator->translate('open.banking.not.configured')
    );
}