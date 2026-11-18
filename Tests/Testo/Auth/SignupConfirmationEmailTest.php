<?php

declare(strict_types=1);

namespace Tests\Testo\Auth;

use App\Auth\SignupConfirmationEmail;
use Testo\Assert;
use Testo\Test;

/**
 * The signup confirmation email must show the confirm link in any mail viewer: as a link in a complete
 * HTML document, with the address in full underneath, and as plain text.
 *
 * @see SignupConfirmationEmail
 */
#[Test]
final class SignupConfirmationEmailTest
{
    private const string URL = 'https://example.test/en/userinv/signup/English/AbC-123_xyz/email-verification';

    public function theHtmlIsACompleteDocumentWithTheLinkAndTheAddressWrittenOut(): void
    {
        $html = SignupConfirmationEmail::html(self::URL, 'Please confirm');

        Assert::true(str_starts_with($html, '<!DOCTYPE html><html>'));
        Assert::true(str_contains($html, '<meta charset="utf-8">'));
        Assert::true(str_contains($html, '<a href="' . self::URL . '">Please confirm</a>'));
        // the address is also shown as text, for viewers that do not render or allow links
        Assert::true(str_contains($html, '<p>' . self::URL . '</p>'));
        Assert::true(str_ends_with($html, '</body></html>'));
    }

    public function theAddressIsEscapedSoATokenCannotBreakOutOfTheMarkup(): void
    {
        $url = 'https://example.test/x?a=1&b="><script>alert(1)</script>';
        $html = SignupConfirmationEmail::html($url, 'Please <b>confirm</b>');

        Assert::false(str_contains($html, '<script>'));
        Assert::false(str_contains($html, '<b>confirm</b>'));
        Assert::true(str_contains($html, '&lt;script&gt;'));
    }

    public function thePlainTextHasTheLinkTextAndTheFullAddress(): void
    {
        Assert::same(
            SignupConfirmationEmail::text(self::URL, 'Please confirm'),
            "Please confirm\n\n" . self::URL . "\n",
        );
    }
}
