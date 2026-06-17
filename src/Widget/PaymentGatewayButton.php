<?php

declare(strict_types=1);

namespace App\Widget;

use Yiisoft\Html\Html;
use Yiisoft\Html\Tag\Img;

final class PaymentGatewayButton
{
    public static function amazon(): string
    {
        return
        Html::openTag('div', ['class' => 'btn-group', 'role' => 'group'])
            .  new Img()
            ->size(40, 25)
            ->src('/img/amazon.png')
            ->addClass('btn btn-warning')
            ->render()
        . Html::closeTag('div');
    }

    public static function braintree(): string
    {
        return
        Html::openTag('div', ['class' => 'btn-group', 'role' => 'group'])
            .  new Img()
            ->size(100, 50)
            ->src('/img/braintree.png')
            ->addClass('btn btn-light')
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
            ->addClass('btn btn-light')
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
            ->addClass('btn btn-light')
            ->render()
        . Html::closeTag('div');
    }
}
