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

    private static function squareIconButton(string $name, string $ext = 'svg'): string
    {
        return
        Html::openTag('div', ['class' => 'btn-group', 'role' => 'group'])
            .  new Img()
            ->size(50, 50)
            ->src('/img/' . $name . '.' . $ext)
            ->addClass(self::BTN_LIGHT)
            ->render()
        . Html::closeTag('div');
    }

    /**
     * GoCardless/Paystack/YooKassa -- wide wordmarks (icon + text
     * combined) like braintree.png/stripe.png/mollie.png, not square
     * glyphs -- sized to roughly each one's own aspect ratio rather than
     * the 50x50 square helper.
     *
     * GoCardless: Wikimedia Commons (CC BY-SA 4.0, sourced from
     * gocardless.com per the file's own description), viewBox
     * 1000x148.82.
     * Paystack: Wikimedia Commons (own work, uploaded by Ultramart.africa),
     * viewBox 0 0 157 28.
     * YooKassa: Wikimedia Commons (described at promo.yookassa.ru/agents),
     * viewBox 0 0 328 80.
     */
    public static function gocardless(): string
    {
        return self::wordmarkButton('gocardless', 200, 30);
    }

    public static function paystack(): string
    {
        return self::wordmarkButton('paystack', 140, 25);
    }

    public static function yookassa(): string
    {
        return self::wordmarkButton('yookassa', 125, 30);
    }

    private static function wordmarkButton(string $name, int $width, int $height): string
    {
        return
        Html::openTag('div', ['class' => 'btn-group', 'role' => 'group'])
            .  new Img()
            ->size($width, $height)
            ->src('/img/' . $name . '.svg')
            ->addClass(self::BTN_LIGHT)
            ->render()
        . Html::closeTag('div');
    }

    /**
     * Checkout.com/Robokassa/TrueLayer/Mercado Pago -- none of these are
     * in Simple Icons or on Wikimedia Commons at all (checked live, zero
     * hits) -- sourced directly from each company's own site instead
     * (the apple-touch-icon/favicon link tags in their own page <head>),
     * guaranteed official since it comes straight from their own server.
     * Square icons like paypal/adyen/square/razorpay above.
     *
     * Checkout.com: cdn.prod.website-files.com (their own Webflow-hosted
     * apple-touch-icon), 256x256 PNG.
     * Robokassa: robokassa.com/favicon.ico -- a single 256x256 PNG-format
     * ICO entry; the raw PNG bytes were extracted directly (ICO stores a
     * PNG-compressed entry as the complete PNG file verbatim) since this
     * app has no other .ico usage anywhere and PNG matches every other
     * gateway image's format.
     * TrueLayer: truelayer.com/favicon.svg, 512x512, has its own
     * dark-mode-aware CSS baked in (prefers-color-scheme).
     * Mercado Pago: their own CDN (http2.mlstatic.com), 64x64.
     */
    public static function checkoutCom(): string
    {
        return self::squareIconButton('checkout_com', 'png');
    }

    public static function robokassa(): string
    {
        return self::squareIconButton('robokassa', 'png');
    }

    public static function truelayer(): string
    {
        return self::squareIconButton('truelayer');
    }

    public static function mercadoPago(): string
    {
        return self::squareIconButton('mercado_pago');
    }

    /**
     * BitPay -- not in Simple Icons (checked live, 404 on every plausible
     * slug) or Wikimedia Commons -- sourced directly from bitpay.com's own
     * page <head> (apple-touch-icon link tag, hosted on their own
     * framerusercontent.com CDN), same reasoning as
     * checkoutCom()/robokassa()/truelayer()/mercadoPago() above. 180x180 PNG.
     */
    public static function bitpay(): string
    {
        return self::squareIconButton('bitpay', 'png');
    }
}
