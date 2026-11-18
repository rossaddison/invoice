<?php

declare(strict_types=1);

namespace Tests\Testo\Invoice\Telegram;

use App\Invoice\Telegram\OutstandingInvoiceReminder;
use Testo\Assert;
use Testo\Test;

/**
 * The reminder must say nothing about the invoice (a bot message is stored in the clear), carry a button to
 * the login-protected invoice page, and be protected against forwarding.
 *
 * @see OutstandingInvoiceReminder
 */
#[Test]
final class OutstandingInvoiceReminderTest
{
    private const string BASE = 'https://invoice.example.test';

    public function theTextCarriesNoInvoiceNumberAndNoAmount(): void
    {
        foreach ([OutstandingInvoiceReminder::TEXT_WITH_BUTTON, OutstandingInvoiceReminder::TEXT_WITHOUT_BUTTON] as $text) {
            Assert::false(preg_match('/\d/', $text) === 1, $text);
            Assert::false(str_contains($text, '#'), $text);
            Assert::false(str_contains($text, 'balance'), $text);
        }
    }

    public function theButtonPointsAtTheInvoicePage(): void
    {
        $message = OutstandingInvoiceReminder::message(self::BASE, '/en/inv/view/12');

        Assert::same($message['text'], OutstandingInvoiceReminder::TEXT_WITH_BUTTON);
        $markup = $message['replyMarkup'];
        Assert::true($markup !== null);
        $row = $markup->inlineKeyboard[0] ?? [];
        Assert::same(count($row), 1);
        Assert::same($row[0]->text, 'View invoice');
        Assert::same($row[0]->url, 'https://invoice.example.test/en/inv/view/12');
    }

    public function aTrailingSlashOnTheBaseAddressDoesNotDoubleUp(): void
    {
        $markup = OutstandingInvoiceReminder::message(self::BASE . '/', '/en/inv/view/3')['replyMarkup'];

        Assert::same($markup?->inlineKeyboard[0][0]->url, 'https://invoice.example.test/en/inv/view/3');
    }

    public function theMessageCannotBeForwardedOrSaved(): void
    {
        Assert::true(OutstandingInvoiceReminder::message(self::BASE, '/en/inv/view/1')['protectContent']);
        Assert::true(OutstandingInvoiceReminder::message('', '/en/inv/view/1')['protectContent']);
    }

    public function withoutABaseAddressThereIsNoButtonAndTheTextAsksForALogin(): void
    {
        $message = OutstandingInvoiceReminder::message('', '/en/inv/view/12');

        Assert::null($message['replyMarkup']);
        Assert::same($message['text'], OutstandingInvoiceReminder::TEXT_WITHOUT_BUTTON);
    }

    public function anAddressTelegramWouldRejectYieldsNoButton(): void
    {
        // Telegram refuses the whole message for a URL button it cannot use, so each of these must send plain text
        foreach (['invoice.example.test', 'ftp://invoice.example.test', 'https://', '   ', 'javascript:alert(1)//'] as $base) {
            Assert::null(OutstandingInvoiceReminder::message($base, '/en/inv/view/12')['replyMarkup'], $base);
        }
        // and a path that does not start with a slash cannot be joined to the address
        Assert::null(OutstandingInvoiceReminder::message(self::BASE, 'en/inv/view/12')['replyMarkup']);
    }
}
