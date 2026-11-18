<?php

declare(strict_types=1);

namespace App\Invoice\Telegram;

use Phptg\BotApi\Type\InlineKeyboardButton;
use Phptg\BotApi\Type\InlineKeyboardMarkup;

/**
 * The Telegram reminder for an invoice with a balance outstanding.
 *
 * A bot message is not end-to-end encrypted: Telegram stores it in the clear. So the text carries nothing
 * about the invoice, no number and no amount, and the details sit behind a button to the invoice page,
 * which asks for a login. The message is also sent with protect_content, so it cannot be forwarded or saved
 * from the chat.
 *
 * The button is built from the site's public address (APP_URL, see config/common/params.php 'app.baseUrl'),
 * which a job running without a request has to be given. Without one there is no button, and the text asks
 * the reader to log in instead.
 */
final class OutstandingInvoiceReminder
{
    public const string TEXT_WITH_BUTTON = 'You have an invoice awaiting payment.';
    public const string TEXT_WITHOUT_BUTTON = 'You have an invoice awaiting payment. Please log in to view it.';
    public const string BUTTON_LABEL = 'View invoice';

    /**
     * @param string $baseUrl the public address of the site, e.g. https://invoice.example.test (may be '')
     * @param string $invoicePath the invoice page's path, starting with a slash, e.g. /en/inv/view/12
     *
     * @return array{text: string, replyMarkup: InlineKeyboardMarkup|null, protectContent: true}
     */
    public static function message(string $baseUrl, string $invoicePath): array
    {
        $url = self::buttonUrl($baseUrl, $invoicePath);
        if (null === $url) {
            return ['text' => self::TEXT_WITHOUT_BUTTON, 'replyMarkup' => null, 'protectContent' => true];
        }
        return [
            'text' => self::TEXT_WITH_BUTTON,
            'replyMarkup' => new InlineKeyboardMarkup([[new InlineKeyboardButton(self::BUTTON_LABEL, url: $url)]]),
            'protectContent' => true,
        ];
    }

    /**
     * Telegram accepts only a complete http(s) address for a URL button, and rejects the whole message
     * otherwise, so anything else yields no button.
     */
    private static function buttonUrl(string $baseUrl, string $invoicePath): ?string
    {
        $baseUrl = rtrim(trim($baseUrl), '/');
        if ($baseUrl === '' || !str_starts_with($invoicePath, '/')) {
            return null;
        }
        $url = $baseUrl . $invoicePath;
        $parts = parse_url($url);
        if (false === $parts || !in_array($parts['scheme'] ?? '', ['http', 'https'], true) || ($parts['host'] ?? '') === '') {
            return null;
        }
        return false === filter_var($url, FILTER_VALIDATE_URL) ? null : $url;
    }
}
