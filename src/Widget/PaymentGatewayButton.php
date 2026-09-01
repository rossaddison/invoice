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

    /**
     * PayPal/Adyen/Square/Razorpay -- Simple Icons (simpleicons.org, CC0
     * for the icon glyphs themselves, brand marks used per each company's
     * own guidelines for identifying their payment method) rather than
     * this app's own asset, since public/img/ had no PayPal/Adyen/Square/
     * Razorpay logo of any kind despite those gateways being fully
     * integrated (found 2026-09-01, same investigation as the "Pay Now"
     * dropdown text-fallback fix). Square SVG glyphs (viewBox 0 0 24 24),
     * not wordmarks like braintree.png/stripe.png/mollie.png -- sized
     * 50x50 rather than those three's wide dimensions accordingly.
     */
    public static function paypal(): string
    {
        return self::squareIconButton('paypal');
    }

    public static function adyen(): string
    {
        return self::squareIconButton('adyen');
    }

    public static function square(): string
    {
        return self::squareIconButton('square');
    }

    public static function razorpay(): string
    {
        return self::squareIconButton('razorpay');
    }

    private static function squareIconButton(string $name): string
    {
        return
        Html::openTag('div', ['class' => 'btn-group', 'role' => 'group'])
            .  new Img()
            ->size(50, 50)
            ->src('/img/' . $name . '.svg')
            ->addClass(self::BTN_LIGHT)
            ->render()
        . Html::closeTag('div');
    }

    /**
     * GoCardless -- Wikimedia Commons (CC BY-SA 4.0, sourced from
     * gocardless.com per the file's own description), a wide wordmark
     * (viewBox 1000x148.82, icon + text combined) like
     * braintree.png/stripe.png/mollie.png rather than the square glyphs
     * used for paypal/adyen/square/razorpay -- sized to roughly the same
     * aspect ratio (200x30) instead of the 50x50 helper.
     */
    public static function gocardless(): string
    {
        return
        Html::openTag('div', ['class' => 'btn-group', 'role' => 'group'])
            .  new Img()
            ->size(200, 30)
            ->src('/img/gocardless.svg')
            ->addClass(self::BTN_LIGHT)
            ->render()
        . Html::closeTag('div');
    }
}
