<?php

declare(strict_types=1);

namespace App\Invoice\Telegram;

use App\Infrastructure\Persistence\Payment\Payment;
use App\Invoice\BaseController;
use App\Invoice\Helpers\Telegram\TelegramHelper;
use App\Invoice\Inv\InvRepository as IR;
use App\Invoice\Payment\PaymentRepository as PR;
use App\Invoice\Payment\PaymentService;
use App\Invoice\Setting\SettingRepository as sR;
use App\Service\WebControllerService;
use App\User\UserService;
use App\Widget\Button;
use JsonException;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Log\LoggerInterface as Logger;
use Phptg\BotApi\FailResult;
use Phptg\BotApi\ParseResult\TelegramParseResultException;
use Phptg\BotApi\TelegramBotApi;
use Yiisoft\DataResponse\ResponseFactory\DataResponseFactoryInterface;
use Yiisoft\Json\Json;
use Yiisoft\Router\FastRoute\UrlGenerator;
use Yiisoft\Router\HydratorAttribute\RouteArgument;
use Yiisoft\Session\Flash\Flash;
use Yiisoft\Session\SessionInterface;
use Yiisoft\Translator\TranslatorInterface;
use Yiisoft\Yii\View\Renderer\WebViewRenderer;

final class TelegramController extends BaseController
{
    // Not injected via DI — requires a runtime token from settings;
    // set lazily by action methods via TelegramHelper::getBotApi().
    private ?TelegramBotApi $telegramBotApi = null;

    public function __construct(
        SessionInterface $session,
        sR $sR,
        TranslatorInterface $translator,
        UserService $userService,
        WebViewRenderer $webViewRenderer,
        WebControllerService $webService,
        private DataResponseFactoryInterface $factory,
        private Logger $logger,
        Flash $flash,
        private IR $iR,
        private PaymentService $paymentService,
    ) {
        parent::__construct($webService, $userService, $translator,
                $webViewRenderer, $session, $sR, $flash);
    }

    public function index(UrlGenerator $urlGenerator): Response
    {
        $settingRepositoryTelegramToken = $this->sR->getSetting('telegram_token');
        $chatId = $this->sR->getSetting('telegram_chat_id');
        $ipAddress = null;
        $businessConnectionId = null;
        $maxConnections = null;
        $allowUpdates = null;
        $dropPendingUpdates = null;
        $messageThreadId = null;
        $parseMode = null;
        $entities = null;
        $linkPreviewOptions = null;
        $disableNotification = null;
        $protectContent = null;
        $messageEffectId = null;
        $replyParameters = null;
        $replyMarkup = null;
        $allowPaidBroadcast = null;
        $secretToken = $this->sR->getSetting('telegram_webhook_secret_token') ?: null;
        try {
            $telegramEnabled = $this->sR->getSetting('enable_telegram');
            if ($telegramEnabled == '1') {
                if (strlen($settingRepositoryTelegramToken) > 1) {
                    $telegramHelper = new TelegramHelper(
                        $settingRepositoryTelegramToken,
                        $this->logger,
                    );
                    $this->telegramBotApi = $telegramHelper->getBotApi();
                    // FailResult|true
                    $failResult = $telegramHelper->setWebhook(
                        $urlGenerator,
                        $ipAddress,
                        $maxConnections,
                        $allowUpdates,
                        $dropPendingUpdates,
                        $secretToken,
                    );
                    if (strlen($chatId) > 1) {
                        if (!$failResult instanceof FailResult) {
                            $user = $this->telegramBotApi->getMe();
                            if (($user instanceof \Phptg\BotApi\Type\User)
                                && ($this->sR->getSetting('telegram_test_message_use') == '1')) {
                                $text = $this->translator->translate(
                                        'telegram.bot.api.hello.world.test.message');
                                $sendMessageResult = $this->telegramBotApi->sendMessage(
                                    $chatId,
                                    $text,
                                    $businessConnectionId,
                                    $messageThreadId,
                                    $parseMode,
                                    $entities,
                                    $linkPreviewOptions,
                                    $disableNotification,
                                    $protectContent,
                                    $messageEffectId,
                                    $replyParameters,
                                    $replyMarkup,
                                    $allowPaidBroadcast,
                                );
                                if (!$sendMessageResult instanceof FailResult) {
                                    $this->flashMessage('success',
                                        $this->translator->translate(
                                                'telegram.bot.api.'
                                                . 'hello.world.test.message.sent'));
                                } else {
                                    $this->flashMessage('danger',
                                            $this->translator->translate(
                                                    'telegram.bot.api.'
                                                    . 'hello.world.test.'
                                                    . 'message.sent.not'));
                                    if (null !== $sendMessageResult->description) {
                                        $this->flashMessage('primary', 'Fail Result: '
                                                . $sendMessageResult->description);
                                    }
                                    if (null !== $sendMessageResult->errorCode) {
                                        $match = match ($sendMessageResult->errorCode) {
                                            403 => 'Solution: 1. Send a'
                                            . ' Message to Your Bot: Open'
                                            . ' Telegram and search for your bot'
                                            . ' by its username.'
                                            . 'Start a chat with your bot and'
                                            . ' send any message to it.'
                                            . ' 2. Open your browser and enter'
                                            . ' the following URL,'
                                            . ' replacing YOUR_BOT_TOKEN with'
                                            . ' your bot token:'
                                            . ' https://api.telegram.org/'
                                            . 'botYOUR_BOT_TOKEN/getUpdates'
                                            . Button::deleteWebhook(
                                                $urlGenerator,
                                                $this->translator),
                                            409 => Button::deleteWebhook(
                                                    $urlGenerator,
                                                    $this->translator),
                                            default =>
                                                $sendMessageResult->description ?? '',
                                        };
                                        $this->flashMessage('primary',
                                            'Fail Result: '
                                            . (string) $sendMessageResult->errorCode
                                            . ' ' . $match);
                                    }
                                    $this->webService->getRedirectResponse('setting/tabIndex');
                                }
                            }
                        } else {
                            if (null !== $failResult->description) {
                                $this->flashMessage('primary', 'Fail Result: '
                                    . $failResult->description);
                            }
                            if (null !== $failResult->errorCode) {
                                $this->flashMessage('primary', 'Fail Result: '
                                    . (string) $failResult->errorCode);
                            }
                        }
                    } else {
                        $this->flashMessage('danger',
                                $this->translator->translate(
                                        'telegram.bot.api.chat.id.not.set'));
                    }
                } else {
                    $this->flashMessage('danger',
                                $this->translator->translate(
                                        'telegram.bot.api.token.not.set'));
                    $this->webService->getRedirectResponse('setting/tabIndex');
                }
            } else {
                $this->flashMessage('danger',
                        $this->translator->translate(
                                'telegram.bot.api.enabled.not'));
                $this->webService->getRedirectResponse('setting/tabIndex');
            }
        } catch (TelegramParseResultException $e) {
            $this->logger->warning($e->getMessage());
            $this->flashMessage('secondary', $e->getMessage());
        }
        return $this->webViewRenderer->render('index', $parameters = [
            'alert' => $this->alert(),
        ]);
    }

    /**
     * Sends a Telegram native invoice for the given inv_id to the chat_id
     * configured in settings (telegram_chat_id). For per-client delivery a
     * telegram_chat_id column on the Client entity would be required.
     *
     * Prerequisites: enable_telegram = 1, telegram_token set,
     * telegram_provider_token set (from @BotFather / Stripe), telegram_chat_id set.
     */
    public function sendInvoice(
        #[RouteArgument('inv_id')]
        string $inv_id,
    ): Response {
        $token         = $this->sR->getSetting('telegram_token');
        $providerToken = $this->sR->getSetting('telegram_provider_token');
        $currency      = $this->sR->getSetting('peppol_document_currency') ?: 'GBP';
        try {
            if ($this->sR->getSetting('enable_telegram') !== '1') {
                $this->flashMessage('danger',
                    $this->translator->translate('telegram.bot.api.enabled.not'));
                return $this->webService->getRedirectResponse('setting/tabIndex');
            }
            if (strlen($token) < 2) {
                $this->flashMessage('danger',
                    $this->translator->translate('telegram.bot.api.token.not.set'));
                return $this->webService->getRedirectResponse('setting/tabIndex');
            }
            $inv = $this->iR->repoInvLoadedquery((int) $inv_id);
            if ($inv === null) {
                $this->flashMessage('danger', 'Invoice not found.');
                return $this->webService->getRedirectResponse('inv/index');
            }
            $chatId = $inv->getClient()?->getClientTelegramChatId();
            if ($chatId === null || $chatId === '') {
                $this->flashMessage('danger',
                    $this->translator->translate('telegram.invoice.client.chat.id.not.set'));
                return $this->webService->getRedirectResponse('inv/view', ['id' => $inv_id]);
            }
            $telegramHelper = new TelegramHelper($token, $this->logger);
            $result = $telegramHelper->sendTelegramInvoice($chatId, $inv, $currency, $providerToken);
            if (!$result instanceof FailResult) {
                $this->flashMessage('success',
                    $this->translator->translate('telegram.invoice.sent'));
            } else {
                $this->flashMessage('danger',
                    'Telegram sendInvoice failed: ' . ($result->description ?? ''));
                if ($result->errorCode !== null) {
                    $this->flashMessage('primary', 'Error code: ' . (string) $result->errorCode);
                }
            }
        } catch (TelegramParseResultException $e) {
            $this->logger->warning($e->getMessage());
            $this->flashMessage('secondary', $e->getMessage());
        } catch (JsonException $e) {
            $this->logger->warning($e->getMessage());
            $this->flashMessage('danger', 'Failed to encode invoice payload: ' . $e->getMessage());
        }
        return $this->webService->getRedirectResponse('inv/view', ['id' => $inv_id]);
    }

    /**
     * Creates a shareable Telegram payment link for the invoice and flashes
     * it to the user. The link can be shared via email, SMS, or any channel —
     * no Telegram chat ID required.
     */
    /**
     * createInvoiceLink does not need a chat ID (the link can be shared anywhere),
     * but still requires the invoice to exist and Telegram to be enabled.
     */
    public function createInvoiceLink(
        #[RouteArgument('inv_id')]
        string $inv_id,
    ): Response {
        $token         = $this->sR->getSetting('telegram_token');
        $providerToken = $this->sR->getSetting('telegram_provider_token');
        $currency      = $this->sR->getSetting('peppol_document_currency') ?: 'GBP';
        try {
            if ($this->sR->getSetting('enable_telegram') !== '1') {
                $this->flashMessage('danger',
                    $this->translator->translate('telegram.bot.api.enabled.not'));
                return $this->webService->getRedirectResponse('setting/tabIndex');
            }
            if (strlen($token) < 2) {
                $this->flashMessage('danger',
                    $this->translator->translate('telegram.bot.api.token.not.set'));
                return $this->webService->getRedirectResponse('setting/tabIndex');
            }
            $inv = $this->iR->repoInvLoadedquery((int) $inv_id);
            if ($inv === null) {
                $this->flashMessage('danger', 'Invoice not found.');
                return $this->webService->getRedirectResponse('inv/index');
            }
            $telegramHelper = new TelegramHelper($token, $this->logger);
            $result = $telegramHelper->createTelegramInvoiceLink($inv, $currency, $providerToken);
            if (!$result instanceof FailResult) {
                $this->flashMessage('success',
                    $this->translator->translate('telegram.invoice.link.created'));
                $this->flashMessage('primary', $result);
            } else {
                $this->flashMessage('danger',
                    'Telegram createInvoiceLink failed: ' . ($result->description ?? ''));
            }
        } catch (TelegramParseResultException $e) {
            $this->logger->warning($e->getMessage());
            $this->flashMessage('secondary', $e->getMessage());
        } catch (JsonException $e) {
            $this->logger->warning($e->getMessage());
            $this->flashMessage('danger', 'Failed to encode invoice payload: ' . $e->getMessage());
        }
        return $this->webService->getRedirectResponse('inv/view', ['id' => $inv_id]);
    }

    /**
     * Sends the most recently archived PDF for the invoice to the Telegram chat.
     * The PDF must have been generated via Options → Download PDF at least once.
     */
    public function sendPdf(
        #[RouteArgument('inv_id')]
        string $inv_id,
    ): Response {
        $token = $this->sR->getSetting('telegram_token');
        try {
            if ($this->sR->getSetting('enable_telegram') !== '1') {
                $this->flashMessage('danger',
                    $this->translator->translate('telegram.bot.api.enabled.not'));
                return $this->webService->getRedirectResponse('setting/tabIndex');
            }
            if (strlen($token) < 2) {
                $this->flashMessage('danger',
                    $this->translator->translate('telegram.bot.api.token.not.set'));
                return $this->webService->getRedirectResponse('setting/tabIndex');
            }
            $inv = $this->iR->repoInvLoadedquery((int) $inv_id);
            if ($inv === null) {
                $this->flashMessage('danger', 'Invoice not found.');
                return $this->webService->getRedirectResponse('inv/index');
            }
            $chatId = $inv->getClient()?->getClientTelegramChatId();
            if ($chatId === null || $chatId === '') {
                $this->flashMessage('danger',
                    $this->translator->translate('telegram.invoice.client.chat.id.not.set'));
                return $this->webService->getRedirectResponse('inv/view', ['id' => $inv_id]);
            }
            $number    = $inv->getNumber() ?? (string) $inv->reqId();
            $archiveDir = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR
                . 'Uploads' . DIRECTORY_SEPARATOR . 'Archive';
            $globResult = glob($archiveDir . DIRECTORY_SEPARATOR . '*_' . $number . '.pdf');
            /** @var list<string> $matches */
            $matches = $globResult === false ? [] : $globResult;
            if ($matches === []) {
                $this->flashMessage('warning',
                    $this->translator->translate('telegram.pdf.not.found'));
                return $this->webService->getRedirectResponse('inv/view', ['id' => $inv_id]);
            }
            // Most recently modified file wins
            usort($matches, static fn(string $a, string $b): int => (int) filemtime($b) - (int) filemtime($a));
            $pdfPath = $matches[0];
            $telegramHelper = new TelegramHelper($token, $this->logger);
            $result = $telegramHelper->sendInvoicePdf(
                $chatId,
                $pdfPath,
                'Invoice #' . $number,
            );
            if (!$result instanceof FailResult) {
                $this->flashMessage('success',
                    $this->translator->translate('telegram.pdf.sent'));
            } else {
                $this->flashMessage('danger',
                    'Telegram sendDocument failed: ' . ($result->description ?? ''));
            }
        } catch (TelegramParseResultException $e) {
            $this->logger->warning($e->getMessage());
            $this->flashMessage('secondary', $e->getMessage());
        }
        return $this->webService->getRedirectResponse('inv/view', ['id' => $inv_id]);
    }

    /**
     * Sends the company's geographic location to the Telegram chat.
     * Requires company_latitude and company_longitude to be configured in
     * Settings → Telegram (or Settings → Company).
     */
    public function sendLocation(): Response
    {
        $token     = $this->sR->getSetting('telegram_token');
        $chatId    = $this->sR->getSetting('telegram_chat_id');
        $latString = $this->sR->getSetting('company_latitude');
        $lngString = $this->sR->getSetting('company_longitude');
        try {
            if ($this->sR->getSetting('enable_telegram') !== '1') {
                $this->flashMessage('danger',
                    $this->translator->translate('telegram.bot.api.enabled.not'));
                return $this->webService->getRedirectResponse('setting/tabIndex');
            }
            if (strlen($token) < 2) {
                $this->flashMessage('danger',
                    $this->translator->translate('telegram.bot.api.token.not.set'));
                return $this->webService->getRedirectResponse('setting/tabIndex');
            }
            if ($latString === '' || $lngString === '') {
                $this->flashMessage('warning',
                    $this->translator->translate('telegram.location.not.configured'));
                return $this->webService->getRedirectResponse('setting/tabIndex');
            }
            $telegramHelper = new TelegramHelper($token, $this->logger);
            $result = $telegramHelper->sendCompanyLocation(
                $chatId,
                (float) $latString,
                (float) $lngString,
            );
            if (!$result instanceof FailResult) {
                $this->flashMessage('success',
                    $this->translator->translate('telegram.location.sent'));
            } else {
                $this->flashMessage('danger',
                    'Telegram sendLocation failed: ' . ($result->description ?? ''));
            }
        } catch (TelegramParseResultException $e) {
            $this->logger->warning($e->getMessage());
            $this->flashMessage('secondary', $e->getMessage());
        }
        return $this->webService->getRedirectResponse('setting/tabIndex');
    }

    /**
     * Refunds a Telegram Stars (XTR) payment.
     * The payment record's note must contain the charge ID and buyer's Telegram
     * user ID in the format written by the webhook:
     *   "Telegram: {chargeId} | tguid:{userId}"
     * Only XTR-currency payments can be refunded this way.
     */
    public function refundStars(
        #[RouteArgument('payment_id')]
        string $payment_id,
        PR $pR,
    ): Response {
        $token = $this->sR->getSetting('telegram_token');
        try {
            if ($this->sR->getSetting('enable_telegram') !== '1') {
                $this->flashMessage('danger',
                    $this->translator->translate('telegram.bot.api.enabled.not'));
                return $this->webService->getRedirectResponse('setting/tabIndex');
            }
            if (strlen($token) < 2) {
                $this->flashMessage('danger',
                    $this->translator->translate('telegram.bot.api.token.not.set'));
                return $this->webService->getRedirectResponse('setting/tabIndex');
            }
            $payment = $pR->repoPaymentquery((int) $payment_id);
            if ($payment === null) {
                $this->flashMessage('danger', 'Payment not found.');
                return $this->webService->getRedirectResponse('payment/index');
            }
            $note = $payment->getNote();
            // Expected format: "Telegram: {chargeId} | tguid:{userId}"
            if (!preg_match('/Telegram:\s*(\S+)\s*\|\s*tguid:(\d+)/', $note, $m)) {
                $this->flashMessage('warning',
                    $this->translator->translate('telegram.stars.no.charge.id'));
                return $this->webService->getRedirectResponse('payment/index');
            }
            $chargeId = $m[1];
            $userId   = (int) $m[2];
            $telegramHelper = new TelegramHelper($token, $this->logger);
            $result = $telegramHelper->refundStarPayment($userId, $chargeId);
            if ($result === true) {
                $this->flashMessage('success',
                    $this->translator->translate('telegram.stars.refunded'));
            } else {
                $this->flashMessage('danger',
                    'Telegram refundStarPayment failed: ' . ($result->description ?? ''));
            }
        } catch (TelegramParseResultException $e) {
            $this->logger->warning($e->getMessage());
            $this->flashMessage('secondary', $e->getMessage());
        }
        return $this->webService->getRedirectResponse('payment/index');
    }

    /**
     * Receives all Telegram webhook updates. Verifies the secret token from
     * the X-Telegram-Bot-Api-Secret-Token header, then:
     *   - pre_checkout_query  → answerPreCheckoutQuery (must respond within 10 s)
     *   - message.successfulPayment → decodes invoicePayload, records payment
     *
     * IMPORTANT: the route for this action must NOT have auth middleware so
     * Telegram's servers can reach it. Secure it with telegram_webhook_secret_token only.
     */
    public function webhook(Request $request): Response
    {
        $token       = $this->sR->getSetting('telegram_token');
        $secretToken = $this->sR->getSetting('telegram_webhook_secret_token');
        $incoming    = $request->getHeaderLine('X-Telegram-Bot-Api-Secret-Token');

        if (strlen($token) < 2) {
            $this->logger->warning(
                $this->translator->translate('telegram.bot.api.token.not.set'));
            return $this->factory->createResponse(
                Json::encode(['fail' => 'token not set']));
        }
        if ($secretToken !== '' && $incoming !== $secretToken) {
            $this->logger->warning('Telegram webhook: secret token mismatch.');
            return $this->factory->createResponse(Json::encode(['fail' => 'unauthorized']));
        }

        $body = $request->getBody()->getContents();
        try {
            $telegramHelper = new TelegramHelper($token, $this->logger);
            $update = TelegramHelper::pushToWebhook($body, $this->logger);

            // pre_checkout_query — must answer within 10 seconds
            if ($update->preCheckoutQuery !== null) {
                $telegramHelper->answerPreCheckoutQuery($update->preCheckoutQuery->id, true);
                return $this->factory->createResponse(Json::encode(['ok' => true]));
            }

            // successful_payment — decode payload and record the payment
            $successfulPayment = $update->message?->successfulPayment;
            if ($successfulPayment !== null) {
                try {
                    /** @var array{inv_id?: int} $payload */
                    $payload = Json::decode($successfulPayment->invoicePayload);
                    $invId = $payload['inv_id'] ?? 0;
                    if ($invId > 0) {
                        $methodId = (int) ($this->sR->getSetting('telegram_payment_method_id') ?: 1);
                        $buyerUserId = $update->message?->from?->id ?? 0;
                        $chargeId    = $successfulPayment->telegramPaymentChargeId;
                        $note = $buyerUserId > 0
                            ? 'Telegram: ' . $chargeId . ' | tguid:' . $buyerUserId
                            : 'Telegram: ' . $chargeId;
                        $this->paymentService->addPaymentViaPaymentHandler(
                            new Payment(),
                            [
                                'payment_method_id' => $methodId,
                                'payment_date'      => new \DateTime(),
                                'amount'            => $successfulPayment->totalAmount / 100.0,
                                'note'              => $note,
                                'inv_id'            => $invId,
                            ],
                        );
                    }
                } catch (JsonException $e) {
                    $this->logger->warning('webhook: bad invoice payload – ' . $e->getMessage());
                }
                return $this->factory->createResponse(Json::encode(['ok' => true]));
            }

            return $this->factory->createResponse(Json::encode(['ok' => true]));
        } catch (TelegramParseResultException $e) {
            $this->logger->warning($e->getMessage());
            return $this->factory->createResponse(Json::encode(['fail' => $e->getMessage()]));
        }
    }

    /**
     * Note: Tested and functional
     * @return Response
     */
    public function getWebhookinfo(): Response
    {
        $settingRepositoryTelegramToken = $this->sR->getSetting('telegram_token');
        $failResultWebhookInfo = '';
        try {
            $telegramEnabled = $this->sR->getSetting('enable_telegram');
            if ($telegramEnabled == '1') {
                if (strlen($settingRepositoryTelegramToken) > 1) {
                    $telegramHelper = new TelegramHelper(
                        $settingRepositoryTelegramToken,
                        $this->logger,
                    );
                    $failResultWebhookInfo = $telegramHelper->getWebhookInfo();
                } else {
                    $this->flashMessage('danger',
                        $this->translator->translate('telegram.bot.api.token.not.set'));
                    $this->webService->getRedirectResponse('setting/tabIndex');
                }
            } else {
                $this->flashMessage('danger',
                    $this->translator->translate('telegram.bot.api.enabled.not'));
                $this->webService->getRedirectResponse('setting/tabIndex');
            }
        } catch (TelegramParseResultException $e) {
            $this->logger->warning($e->getMessage());
            $this->flashMessage('secondary', $e->getMessage());
        }
        return $this->webViewRenderer->render('getwebhookinfo', $parameters = [
            'alert' => $this->alert(),
            'webhookinfo' => $failResultWebhookInfo,
        ]);
    }

    /**
     * Note: Tested and functional
     * @param UrlGenerator $urlGenerator
     * @return Response
     */
    public function setWebhook(UrlGenerator $urlGenerator): Response
    {
        $settingRepositoryTelegramToken = $this->sR->getSetting('telegram_token');
        $failResultWebhookInfo = '';
        try {
            $telegramEnabled = $this->sR->getSetting('enable_telegram');
            if ($telegramEnabled == '1') {
                if (strlen($settingRepositoryTelegramToken) > 1) {
                    $telegramHelper = new TelegramHelper(
                        $settingRepositoryTelegramToken,
                        $this->logger,
                    );
                    $failResultSetWebhook = $telegramHelper->setWebhook(
                        $urlGenerator,
                        // ipAddress
                        null,
                        // maxConnections
                        null,
                        // allowUpdates
                        null,
                        // dropPendingUpdates
                        false,
                        // secretToken
                        null,
                    );
                    $failResultWebhookInfo = $telegramHelper->getWebhookInfo();
                    if (!$failResultSetWebhook instanceof FailResult) {
                        $this->flashMessage('success',
                            $this->translator->translate('telegram.bot.api.'
                                    . 'webhook.setup'));
                    } else {
                        $this->flashMessage('success',
                            $this->translator->translate('telegram.bot.api.'
                                    . 'webhook.setup.already'));
                        if (null !== $failResultSetWebhook->description) {
                            $this->flashMessage('primary', 'Fail Result: '
                                    . $failResultSetWebhook->description);
                        }
                        if (null !== $failResultSetWebhook->errorCode) {
                            $this->flashMessage('primary', 'Fail Result: '
                                    . (string) $failResultSetWebhook->errorCode);
                        }
                    }
                } else {
                    $this->flashMessage('danger',
                        $this->translator->translate('telegram.bot.api.'
                                . 'token.not.set'));
                    $this->webService->getRedirectResponse('setting/tabIndex');
                }
            } else {
                $this->flashMessage('danger',
                        $this->translator->translate('telegram.bot.api.'
                                . 'enabled.not'));
                $this->webService->getRedirectResponse('setting/tabIndex');
            }
        } catch (TelegramParseResultException $e) {
            $this->logger->warning($e->getMessage());
            $this->flashMessage('secondary', $e->getMessage());
        }
        return $this->webViewRenderer->render('setwebhook', $parameters = [
            'alert' => $this->alert(),
            'webhookinfo' => $failResultWebhookInfo,
        ]);
    }

    /**
     * Note: Tested and functional
     * @return Response
     */
    public function deleteWebhook(): Response
    {
        $settingRepositoryTelegramToken = $this->sR->getSetting('telegram_token');
        try {
            $telegramEnabled = $this->sR->getSetting('enable_telegram');
            if ($telegramEnabled == '1') {
                if (strlen($settingRepositoryTelegramToken) > 1) {
                    $telegramHelper = new TelegramHelper(
                        $settingRepositoryTelegramToken,
                        $this->logger,
                    );
                    $this->telegramBotApi = $telegramHelper->getBotApi();
// ensure any existing Webhook is deleted so that can receive updates e.g.
// messages sent from telegram users so that we can build up a database of
// chat_id's that we can send a test message to
                    $failResult = $telegramHelper->deleteWebhook();
                    if (!$failResult instanceof FailResult) {
                        $this->flashMessage('success',
                                $this->translator->translate('telegram.bot.api.'
                                        . 'webhook.deleted'));
                    } else {
                        if (null !== $failResult->description) {
                            $this->flashMessage('primary', 'Fail Result: '
                                    . $failResult->description);
                        }
                        if (null !== $failResult->errorCode) {
                            $this->flashMessage('primary', 'Fail Result: '
                                    . (string) $failResult->errorCode);
                        }
                    }
                } else {
                    $this->flashMessage('danger',
                            $this->translator->translate('telegram.bot.api.'
                                    . 'token.not.set'));
                    $this->webService->getRedirectResponse('setting/tabIndex');
                }
            } else {
                $this->flashMessage('danger',
                        $this->translator->translate('telegram.bot.api.'
                                . 'enabled.not'));
                $this->webService->getRedirectResponse('setting/tabIndex');
            }
        } catch (TelegramParseResultException $e) {
            $this->logger->warning($e->getMessage());
            $this->flashMessage('secondary', $e->getMessage());
        }
        return $this->webViewRenderer->render('index', $parameters = [
            'alert' => $this->alert(),
        ]);
    }

    /**
     * Note: Tested and functional
     * @return Response
     */
    public function getUpdates(): Response
    {
        $settingRepositoryTelegramToken = $this->sR->getSetting('telegram_token');
        $offset = null;
        $limit = null;
        $timeout = null;
        $allowedUpdates = null;
        $failResultUpdates = [];
        try {
            $telegramEnabled = $this->sR->getSetting('enable_telegram');
            if ($telegramEnabled == '1') {
                if (strlen($settingRepositoryTelegramToken) > 1) {
                    $telegramHelper = new TelegramHelper(
                        $settingRepositoryTelegramToken,
                        $this->logger,
                    );
                    $this->telegramBotApi = $telegramHelper->getBotApi();
                    $failResult = $telegramHelper->deleteWebhook();
                    if (!$failResult instanceof FailResult) {
                        $this->flashMessage('success',
                                $this->translator->translate('telegram.bot.api.'
                                        . 'webhook.deleted'));
                    } else {
                        if (null !== $failResult->description) {
                            $this->flashMessage('primary', 'Fail Result: '
                                    . $failResult->description);
                        }
                        if (null !== $failResult->errorCode) {
                            $this->flashMessage('primary', 'Fail Result: '
                                    . (string) $failResult->errorCode);
                        }
                    }
                    $failResultUpdates =
                        $this->telegramBotApi->getUpdates($offset, $limit,
                            $timeout, $allowedUpdates);
                    if (!$failResultUpdates instanceof FailResult) {
                        $this->flashMessage('success',
                            $this->translator->translate('telegram.bot.api.'
                                    . 'get.updates.success'));
                    } else {
                        $this->flashMessage('danger',
                                $this->translator->translate('telegram.bot.api.'
                                        . 'get.updates.danger'));
                        if (null !== $failResultUpdates->description) {
                            $this->flashMessage('primary', 'Fail Result: '
                                    . $failResultUpdates->description);
                        }
                        if (null !== $failResultUpdates->errorCode) {
                            $this->flashMessage('primary', 'Fail Result: '
                                    . (string) $failResultUpdates->errorCode);
                        }
                    }
                } else {
                    $this->flashMessage('danger',
                            $this->translator->translate('telegram.bot.api.'
                                    . 'token.not.set'));
                    $this->webService->getRedirectResponse('setting/tabIndex');
                }
            } else {
                $this->flashMessage('danger',
                    $this->translator->translate('telegram.bot.api.'
                            . 'enabled.not'));
                $this->webService->getRedirectResponse('setting/tabIndex');
            }
        } catch (TelegramParseResultException $e) {
            $this->logger->warning($e->getMessage());
            $this->flashMessage('secondary', $e->getMessage());
        }
        return $this->webViewRenderer->render('updates', $parameters = [
            'alert' => $this->alert(),
            'updates' => $failResultUpdates,
        ]);
    }
}
