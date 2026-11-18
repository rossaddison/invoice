<?php

declare(strict_types=1);

namespace App\Auth;

use Yiisoft\Html\Html;

/**
 * The body of the signup confirmation email (generic and HomeCare): the confirm link, in a complete
 * HTML document, with the address written out underneath, and the same as plain text. Many mail
 * viewers show only the plain-text part, or hide a message whose HTML is nothing but one link, and then
 * the account can never be confirmed; the address in full is the fallback that always works.
 */
final class SignupConfirmationEmail
{
    public static function html(string $url, string $linkText): string
    {
        return '<!DOCTYPE html><html><head><meta charset="utf-8"></head><body>'
            . '<p>' . Html::a($linkText, $url)->render() . '</p>'
            . '<p>' . Html::encode($url) . '</p>'
            . '</body></html>';
    }

    public static function text(string $url, string $linkText): string
    {
        return $linkText . "\n\n" . $url . "\n";
    }
}
