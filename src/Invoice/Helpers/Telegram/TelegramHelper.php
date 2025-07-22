<?php

declare(strict_types=1);

namespace App\Invoice\Helpers\Telegram;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Log\LoggerInterface as Logger;
use Vjik\TelegramBot\Api\FailResult;
use Vjik\TelegramBot\Api\Transport\CurlTransport;
use Vjik\TelegramBot\Api\LogType;
use Vjik\TelegramBot\Api\TelegramBotApi;
use Vjik\TelegramBot\Api\Type\Update\Update;
use Vjik\TelegramBot\Api\Type\Update\WebhookInfo;
use Yiisoft\Router\FastRoute\UrlGenerator;

/**
 * Using the following functions currently:
 * Related logic: see ..\vendor\vjik\telegram-bot-api\src\TelegramBotApi.php  sendInvoice
 * Related logic: see ..\vendor\vjik\telegram-bot-api\src\TelegramBotApi.php  sendLocation
 * Related logic: see ..\vendor\vjik\telegram-bot-api\src\TelegramBotApi.php  sendMessage
 * Related logic: see ..\vendor\vjik\telegram-bot-api\src\TelegramBotApi.php  getUpdates
 * Related logic: see https://core.telegram.org/bots/api#sendInvoice
 * Related logic: see https://core.telegram.org/bots/api#sendLocation
 * Related logic: see https://core.telegram.org/bots/api#sendMessage
 * Related logic: see https://core.telegram.org/bots/api#getupdates
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

    public static function updateFromServerRequest(Request $request, Logger $logger): Update
    {
        return Update::fromServerRequest($request, $logger);
    }

    public static function decodeJsonEncodedUpdatePushedToWebhookFromTelegramApi(string $jsonString, Logger $logger): Update
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
