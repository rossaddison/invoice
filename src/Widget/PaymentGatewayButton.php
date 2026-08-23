<?php

declare(strict_types=1);

namespace App\Widget;

use Yiisoft\Html\Html;
use Yiisoft\Html\Tag\Img;

final class PaymentGatewayButton
{
    private const BTN_LIGHT = 'btn btn-light';

    public static function braintree(): string
    {
        return
        Html::openTag('div', ['class' => 'btn-group', 'role' => 'group'])
            .  new Img()
            ->size(100, 50)
            ->src('/img/braintree.png')
            ->addClass(self::BTN_LIGHT)
            ->render()
        . Html::closeTag('div');
    }

    public static function stripe(): string
    {
        return
        Html::openTag('div', ['class' => 'btn-group', 'role' => 'group'])
            .  new Img()
            ->size(75, 50)
            ->src('/img/stripe.png')
            ->addClass(self::BTN_LIGHT)
            ->render()
        . Html::closeTag('div');
    }

    public static function mollie(): string
    {
        return
        Html::openTag('div', ['class' => 'btn-group', 'role' => 'group'])
            .  new Img()
            ->size(75, 50)
            ->src('/img/mollie.png')
            ->addClass(self::BTN_LIGHT)
            ->render()
        . Html::closeTag('div');
    }
}
