<?php

declare(strict_types=1);

namespace App\Invoice\Helpers\Telegram;

use Http\Client\Curl\Client;
use HttpSoft\Message\RequestFactory;
use HttpSoft\Message\ResponseFactory;
use HttpSoft\Message\StreamFactory;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Log\LoggerInterface as Logger;
use Vjik\TelegramBot\Api\FailResult;
use Vjik\TelegramBot\Api\Client\PsrTelegramClient;
use Vjik\TelegramBot\Api\Client\TelegramResponse;
use Vjik\TelegramBot\Api\Request\TelegramRequestInterface as trI;
use Vjik\TelegramBot\Api\LogType;
use Vjik\TelegramBot\Api\TelegramBotApi;
use Vjik\TelegramBot\Api\Type\Update\Update;
use Vjik\TelegramBot\Api\Type\Update\WebhookInfo;
use Yiisoft\Router\FastRoute\UrlGenerator;

/**
 * Using the following functions currently:
 * @see ..\vendor\vjik\telegram-bot-api\src\TelegramBotApi.php  sendInvoice
 * @see ..\vendor\vjik\telegram-bot-api\src\TelegramBotApi.php  sendLocation
 * @see ..\vendor\vjik\telegram-bot-api\src\TelegramBotApi.php  sendMessage
 * @see ..\vendor\vjik\telegram-bot-api\src\TelegramBotApi.php  getUpdates
 * @see https://core.telegram.org/bots/api#sendInvoice
 * @see https://core.telegram.org/bots/api#sendLocation
 * @see https://core.telegram.org/bots/api#sendMessage
 * @see https://core.telegram.org/bots/api#getupdates
 */

final class TelegramHelper
{
    private TelegramBotApi $botApi;

    public function __construct(
        private string $settingRepositoryTelegramToken,
        private Logger $logger
    ) {
        $this->logger = $logger;
        $responseFactory = new ResponseFactory();
        $requestFactory = new RequestFactory();
        $streamFactory = new StreamFactory();
        $this->botApi = new TelegramBotApi(
            new PsrTelegramClient(
                $this->settingRepositoryTelegramToken,
                $client = new Client($responseFactory, $streamFactory),
                $requestFactory,
                $streamFactory,
            ),
            $this->logger
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
        ?string $secretToken = null
    ): true|FailResult {
        $result = $this->botApi->setWebhook(
            $urlGenerator->generateAbsolute('telegram/webhook', ['_language' => 'en']),
            $ipAddress,
            $maxConnections,
            $allowUpdates,
            $dropPendingUpdates,
            $secretToken
        );
        return $result;
    }

    public function deleteWebhook(): true|FailResult
    {
        return $this->botApi->deleteWebhook(false);
    }

    /**
     *
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
        ?array $allowedUpdates = null
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

    public static function logTypeCreateSendRequestContext(trI $request): array
    {
        return LogType::createSendRequestContext($request);
    }

    public static function logTypeCreateSuccessResultContext(trI $request, TelegramResponse $response, mixed $decodedResponse): array
    {
        return LogType::createSuccessResultContext($request, $response, $decodedResponse);
    }

    public static function logTypeCreateFailResultContext(trI $request, TelegramResponse $response, mixed $decodedResponse): array
    {
        return LogType::createFailResultContext($request, $response, $decodedResponse);
    }

    public static function logTypeCreateParseResultErrorContext(string $raw): array
    {
        return LogType::createParseResultErrorContext($raw);
    }
}
