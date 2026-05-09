<?php

declare(strict_types=1);

namespace App\Invoice\Helpers\Telegram;

use App\Infrastructure\Persistence\Inv\Inv;
use App\Infrastructure\Persistence\InvItem\InvItem;
use JsonException;
use Psr\Log\LoggerInterface as Logger;
use Phptg\BotApi\FailResult;
use Phptg\BotApi\Transport\CurlTransport;
use Phptg\BotApi\LogType;
use Phptg\BotApi\TelegramBotApi;
use Phptg\BotApi\Type\InputFile;
use Phptg\BotApi\Type\Message;
use Phptg\BotApi\Type\Payment\LabeledPrice;
use Phptg\BotApi\Type\Update\Update;
use Phptg\BotApi\Type\Update\WebhookInfo;
use Yiisoft\Router\FastRoute\UrlGenerator;

/**
 * Thin wrapper around phptg/bot-api — a zero-dependency Telegram Bot API
 * library for PHP developed and maintained by Sergei Predvoditelev (vjik).
 *
 * Library:  https://github.com/phptg/bot-api
 * Author:   https://github.com/vjik
 * Support:  https://boosty.to/vjik
 *
 * Methods currently used from TelegramBotApi:
 * - getMe()                  vendor/phptg/bot-api/src/TelegramBotApi.php
 * - sendMessage()            vendor/phptg/bot-api/src/TelegramBotApi.php
 * - sendInvoice()            vendor/phptg/bot-api/src/TelegramBotApi.php
 * - createInvoiceLink()      vendor/phptg/bot-api/src/TelegramBotApi.php
 * - sendDocument()           vendor/phptg/bot-api/src/TelegramBotApi.php
 * - sendLocation()           vendor/phptg/bot-api/src/TelegramBotApi.php
 * - refundStarPayment()      vendor/phptg/bot-api/src/TelegramBotApi.php
 * - answerPreCheckoutQuery() vendor/phptg/bot-api/src/TelegramBotApi.php
 * - setWebhook()             vendor/phptg/bot-api/src/TelegramBotApi.php
 * - deleteWebhook()          vendor/phptg/bot-api/src/TelegramBotApi.php
 * - getWebhookInfo()         vendor/phptg/bot-api/src/TelegramBotApi.php
 * - getUpdates()             vendor/phptg/bot-api/src/TelegramBotApi.php
 *
 * Telegram Bot API references:
 * @see https://core.telegram.org/bots/api#sendmessage
 * @see https://core.telegram.org/bots/api#sendinvoice
 * @see https://core.telegram.org/bots/api#answerprecheckoutquery
 * @see https://core.telegram.org/bots/api#setwebhook
 * @see https://core.telegram.org/bots/api#getupdates
 */

final class TelegramHelper
{
    private readonly TelegramBotApi $botApi;

    public function __construct(
        private readonly string $settingRepositoryTelegramToken,
        private Logger $logger,
    ) {
        $this->logger = $logger;
        $this->botApi = new TelegramBotApi(
            $this->settingRepositoryTelegramToken,
            'https://api.telegram.org',
            new CurlTransport(),
            $this->logger,
        );
    }

    public function getBotApi(): TelegramBotApi
    {
        return $this->botApi;
    }

    public function getWebhookInfo(): FailResult|WebhookInfo
    {
        return $this->botApi->getWebhookInfo();
    }

    public function setWebhook(
        UrlGenerator $urlGenerator,
        ?string $ipAddress = null,
        ?int $maxConnections = null,
        ?array $allowUpdates = null,
        ?bool $dropPendingUpdates = null,
        ?string $secretToken = null,
    ): true|FailResult {
        return $this->botApi->setWebhook(
            $urlGenerator->generateAbsolute('telegram/webhook', ['_language' => 'en']),
            $ipAddress,
            $maxConnections,
            $allowUpdates,
            $dropPendingUpdates,
            $secretToken,
        );
    }

    public function deleteWebhook(): true|FailResult
    {
        return $this->botApi->deleteWebhook(false);
    }

    /**
     * @param int|null $offset
     * @param int|null $limit
     * @param int|null $timeout
     * @param string[]|null $allowedUpdates
     * @return FailResult|Update[]
     */
    public function getUpdates(
        ?int $offset = null,
        ?int $limit = null,
        ?int $timeout = null,
        ?array $allowedUpdates = null,
    ): FailResult|array {
        return $this->botApi->getUpdates($offset, $limit, $timeout, $allowedUpdates);
    }

    /**
     * Sends a Telegram native invoice to the given chat.
     * @throws JsonException
     */
    public function sendTelegramInvoice(
        string $chatId,
        Inv $inv,
        string $currency,
        string $providerToken,
    ): FailResult|Message {
        [$prices, $title, $description, $payload] = $this->buildInvoiceParams($inv);
        return $this->botApi->sendInvoice(
            chatId: $chatId,
            title: $title,
            description: $description,
            payload: $payload,
            currency: $currency,
            prices: $prices,
            providerToken: $providerToken,
        );
    }

    /**
     * Creates a shareable Telegram payment link for the invoice.
     * Unlike sendInvoice, no chat ID is required — the returned URL can be
     * shared anywhere (email, SMS, web page).
     * @throws JsonException
     */
    public function createTelegramInvoiceLink(
        Inv $inv,
        string $currency,
        string $providerToken,
    ): FailResult|string {
        [$prices, $title, $description, $payload] = $this->buildInvoiceParams($inv);
        return $this->botApi->createInvoiceLink(
            title: $title,
            description: $description,
            payload: $payload,
            currency: $currency,
            prices: $prices,
            providerToken: $providerToken,
        );
    }

    /**
     * Sends an archived PDF of the invoice as a Telegram document.
     * $pdfPath must be an absolute path to an existing file.
     */
    public function sendInvoicePdf(
        string $chatId,
        string $pdfPath,
        string $caption,
    ): FailResult|Message {
        return $this->botApi->sendDocument(
            chatId: $chatId,
            document: new InputFile($pdfPath),
            caption: substr($caption, 0, 1024),
        );
    }

    /**
     * Sends the company's geographic location to the chat.
     * Useful for giving clients directions to your premises.
     */
    public function sendCompanyLocation(
        string $chatId,
        float $latitude,
        float $longitude,
    ): FailResult|Message {
        return $this->botApi->sendLocation(
            chatId: $chatId,
            latitude: $latitude,
            longitude: $longitude,
        );
    }

    /**
     * Builds the shared price list, title, description and payload used by
     * both sendTelegramInvoice and createTelegramInvoiceLink.
     *
     * @return array{0: LabeledPrice[], 1: string, 2: string, 3: string}
     * @throws JsonException
     */
    private function buildInvoiceParams(Inv $inv): array
    {
        $prices = [];
        /** @var InvItem $item */
        foreach ($inv->getItems() as $item) {
            $amount = (int) round(($item->getPrice() ?? 0.0) * ($item->getQuantity() ?? 1.0) * 100);
            if ($amount > 0) {
                $prices[] = new LabeledPrice(
                    substr($item->getName() ?? 'Item', 0, 255),
                    $amount,
                );
            }
        }
        $taxTotal = $inv->getInvAmount()->getTaxTotal() ?? 0.0;
        if ($taxTotal > 0.0) {
            $prices[] = new LabeledPrice('Tax', (int) round($taxTotal * 100));
        }
        $number      = $inv->getNumber() ?? (string) $inv->reqId();
        $note        = $inv->getNote();
        $description = ($note !== null && $note !== '') ? $note : 'Invoice #' . $number;
        $payload     = json_encode(['inv_id' => $inv->reqId()], JSON_THROW_ON_ERROR);
        return [
            $prices,
            substr('Invoice #' . $number, 0, 32),
            substr($description, 0, 255),
            $payload,
        ];
    }

    /**
     * Refunds a Telegram Stars (XTR) payment.
     * Only works for payments made with currency = XTR.
     * $userId must be the Telegram user ID of the buyer (stored in the webhook payload).
     * $telegramPaymentChargeId is the charge ID from the successful_payment update.
     *
     * @see https://core.telegram.org/bots/api#refundstarpayment
     */
    public function refundStarPayment(
        int $userId,
        string $telegramPaymentChargeId,
    ): true|FailResult {
        return $this->botApi->refundStarPayment($userId, $telegramPaymentChargeId);
    }

    /**
     * Must be called within 10 seconds of receiving a pre_checkout_query update.
     * Pass $ok = false with an $errorMessage to reject the payment.
     */
    public function answerPreCheckoutQuery(
        string $preCheckoutQueryId,
        bool $ok,
        ?string $errorMessage = null,
    ): true|FailResult {
        return $this->botApi->answerPreCheckoutQuery($preCheckoutQueryId, $ok, $errorMessage);
    }

    public static function pushToWebhook(
        string $jsonString, Logger $logger): Update
    {
        return Update::fromJson($jsonString, $logger);
    }

    public static function logTypeCreateSendRequestContext(): int
    {
        return LogType::SEND_REQUEST;
    }

    public static function logTypeCreateSuccessResultContext(): int
    {
        return LogType::SUCCESS_RESULT;
    }

    public static function logTypeCreateFailResultContext(): int
    {
        return LogType::FAIL_RESULT;
    }

    public static function logTypeCreateParseResultErrorContext(): int
    {
        return LogType::PARSE_RESULT_ERROR;
    }
}
