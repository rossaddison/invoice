<?php

declare(strict_types=1);

namespace App\Invoice\Telegram;

use App\Infrastructure\Persistence\Inv\Inv;
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
        $token = $this->sR->getSetting('telegram_token');
        try {
            $configError = $this->telegramConfigError($token);
            if ($configError !== null) {
                $this->flashMessage('danger',
                    $this->translator->translate($configError));
            } else {
                $this->processIndexAction($token, $urlGenerator);
            }
        } catch (TelegramParseResultException $e) {
            $this->logger->warning($e->getMessage());
            $this->flashMessage('secondary', $e->getMessage());
        }
        return $this->webViewRenderer->render('index', [
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
        $redirectRoute = 'inv/view';
        $redirectArgs  = ['id' => $inv_id];
        try {
            $configError = $this->telegramConfigError($token);
            if ($configError !== null) {
                $this->flashMessage('danger',
                    $this->translator->translate($configError));
                [$redirectRoute, $redirectArgs] = ['setting/tabIndex', []];
            } else {
                [$redirectRoute, $redirectArgs] =
                    $this->sendTelegramInvoiceToClient($token, $inv_id, $providerToken, $currency);
            }
        } catch (TelegramParseResultException $e) {
            $this->logger->warning($e->getMessage());
            $this->flashMessage('secondary', $e->getMessage());
        } catch (JsonException $e) {
            $this->logger->warning($e->getMessage());
            $this->flashMessage('danger', 'Failed to encode invoice payload: ' . $e->getMessage());
        }
        return $this->webService->getRedirectResponse($redirectRoute, $redirectArgs);
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
        $configError = null;
        if ($this->sR->getSetting('enable_telegram') !== '1') {
            $configError = $this->translator->translate('telegram.bot.api.enabled.not');
        } elseif (strlen($token) < 2) {
            $configError = $this->translator->translate('telegram.bot.api.token.not.set');
        }
        if ($configError !== null) {
            $this->flashMessage('danger', $configError);
            return $this->webService->getRedirectResponse('setting/tabIndex');
        }
        $inv = $this->iR->repoInvLoadedquery((int) $inv_id);
        if ($inv === null) {
            $this->flashMessage('danger', 'Invoice not found.');
            return $this->webService->getRedirectResponse('inv/index');
        }
        try {
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
            $configError = $this->telegramConfigError($token);
            if ($configError !== null) {
                $this->flashMessage('danger',
                    $this->translator->translate($configError));
                return $this->webService->getRedirectResponse('setting/tabIndex');
            }
            $inv = $this->iR->repoInvLoadedquery((int) $inv_id);
            if ($inv === null) {
                $this->flashMessage('danger', 'Invoice not found.');
                return $this->webService->getRedirectResponse('inv/index');
            }
            $this->sendPdfDocumentToClient($token, $inv);
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
        if ($this->sR->getSetting('enable_telegram') !== '1') {
            $this->flashMessage('danger',
                $this->translator->translate('telegram.bot.api.enabled.not'));
        } elseif (strlen($token) < 2) {
            $this->flashMessage('danger',
                $this->translator->translate('telegram.bot.api.token.not.set'));
        } elseif ($latString === '' || $lngString === '') {
            $this->flashMessage('warning',
                $this->translator->translate('telegram.location.not.configured'));
        } else {
            try {
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
        $token         = $this->sR->getSetting('telegram_token');
        $redirectRoute = 'payment/index';
        $redirectArgs  = [];
        try {
            $configError = $this->telegramConfigError($token);
            if ($configError !== null) {
                $this->flashMessage('danger',
                    $this->translator->translate($configError));
                $redirectRoute = 'setting/tabIndex';
            } else {
                $redirectRoute = $this->performRefundStars($token, $payment_id, $pR);
            }
        } catch (TelegramParseResultException $e) {
            $this->logger->warning($e->getMessage());
            $this->flashMessage('secondary', $e->getMessage());
        }
        return $this->webService->getRedirectResponse($redirectRoute, $redirectArgs);
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
        $token          = $this->sR->getSetting('telegram_token');
        $secretToken    = $this->sR->getSetting('telegram_webhook_secret_token');
        $incoming       = $request->getHeaderLine('X-Telegram-Bot-Api-Secret-Token');
        $tokenInvalid   = strlen($token) < 2;
        $secretMismatch = $secretToken !== '' && $incoming !== $secretToken;
        if ($tokenInvalid || $secretMismatch) {
            $this->logger->warning($tokenInvalid
                ? $this->translator->translate('telegram.bot.api.token.not.set')
                : 'Telegram webhook: secret token mismatch.');
            $failKey = $tokenInvalid ? 'token not set' : 'unauthorized';
            return $this->factory->createResponse(Json::encode(['fail' => $failKey]));
        }
        try {
            $this->handleWebhookUpdate($token, $request->getBody()->getContents());
        } catch (TelegramParseResultException $e) {
            $this->logger->warning($e->getMessage());
            return $this->factory->createResponse(Json::encode(['fail' => $e->getMessage()]));
        }
        return $this->factory->createResponse(Json::encode(['ok' => true]));
    }

    /**
     * Note: Tested and functional
     * @return Response
     */
    public function getWebhookinfo(): Response
    {
        $token = $this->sR->getSetting('telegram_token');
        $failResultWebhookInfo = '';
        try {
            $configError = $this->telegramConfigError($token);
            if ($configError !== null) {
                $this->flashMessage('danger',
                    $this->translator->translate($configError));
            } else {
                $telegramHelper = new TelegramHelper($token, $this->logger);
                $failResultWebhookInfo = $telegramHelper->getWebhookInfo();
            }
        } catch (TelegramParseResultException $e) {
            $this->logger->warning($e->getMessage());
            $this->flashMessage('secondary', $e->getMessage());
        }
        return $this->webViewRenderer->render('getwebhookinfo', [
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
        $token = $this->sR->getSetting('telegram_token');
        $failResultWebhookInfo = '';
        try {
            $configError = $this->telegramConfigError($token);
            if ($configError !== null) {
                $this->flashMessage('danger',
                    $this->translator->translate($configError));
            } else {
                $failResultWebhookInfo = $this->performSetWebhook($token, $urlGenerator);
            }
        } catch (TelegramParseResultException $e) {
            $this->logger->warning($e->getMessage());
            $this->flashMessage('secondary', $e->getMessage());
        }
        return $this->webViewRenderer->render('setwebhook', [
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
        $token = $this->sR->getSetting('telegram_token');
        try {
            $configError = $this->telegramConfigError($token);
            if ($configError !== null) {
                $this->flashMessage('danger',
                    $this->translator->translate($configError));
            } else {
                $telegramHelper = new TelegramHelper($token, $this->logger);
                $this->telegramBotApi = $telegramHelper->getBotApi();
                $failResult = $telegramHelper->deleteWebhook();
                if (!$failResult instanceof FailResult) {
                    $this->flashMessage('success',
                        $this->translator->translate('telegram.bot.api.webhook.deleted'));
                } else {
                    $this->flashFailResult($failResult);
                }
            }
        } catch (TelegramParseResultException $e) {
            $this->logger->warning($e->getMessage());
            $this->flashMessage('secondary', $e->getMessage());
        }
        return $this->webViewRenderer->render('index', [
            'alert' => $this->alert(),
        ]);
    }

    /**
     * Note: Tested and functional
     * @return Response
     */
    public function getUpdates(): Response
    {
        $token = $this->sR->getSetting('telegram_token');
        $failResultUpdates = [];
        try {
            $configError = $this->telegramConfigError($token);
            if ($configError !== null) {
                $this->flashMessage('danger',
                    $this->translator->translate($configError));
            } else {
                $telegramHelper = new TelegramHelper($token, $this->logger);
                $this->telegramBotApi = $telegramHelper->getBotApi();
                $failResultDelete = $telegramHelper->deleteWebhook();
                if (!$failResultDelete instanceof FailResult) {
                    $this->flashMessage('success',
                        $this->translator->translate('telegram.bot.api.webhook.deleted'));
                } else {
                    $this->flashFailResult($failResultDelete);
                }
                $failResultUpdates =
                    $this->telegramBotApi->getUpdates(null, null, null, null);
                if (!$failResultUpdates instanceof FailResult) {
                    $this->flashMessage('success',
                        $this->translator->translate('telegram.bot.api.get.updates.success'));
                } else {
                    $this->flashMessage('danger',
                        $this->translator->translate('telegram.bot.api.get.updates.danger'));
                    $this->flashFailResult($failResultUpdates);
                }
            }
        } catch (TelegramParseResultException $e) {
            $this->logger->warning($e->getMessage());
            $this->flashMessage('secondary', $e->getMessage());
        }
        return $this->webViewRenderer->render('updates', [
            'alert' => $this->alert(),
            'updates' => $failResultUpdates,
        ]);
    }

    private function telegramConfigError(string $token): ?string
    {
        if ($this->sR->getSetting('enable_telegram') !== '1') {
            return 'telegram.bot.api.enabled.not';
        }
        if (strlen($token) < 2) {
            return 'telegram.bot.api.token.not.set';
        }
        return null;
    }

    private function flashFailResult(FailResult $failResult): void
    {
        if ($failResult->description !== null) {
            $this->flashMessage('primary', 'Fail Result: ' . $failResult->description);
        }
        if ($failResult->errorCode !== null) {
            $this->flashMessage('primary', 'Fail Result: ' . (string) $failResult->errorCode);
        }
    }

    private function processIndexAction(string $token, UrlGenerator $urlGenerator): void
    {
        $chatId      = $this->sR->getSetting('telegram_chat_id');
        $secretToken = $this->sR->getSetting('telegram_webhook_secret_token') ?: null;
        $telegramHelper = new TelegramHelper($token, $this->logger);
        $this->telegramBotApi = $telegramHelper->getBotApi();
        $failResult = $telegramHelper->setWebhook($urlGenerator, null, null, null, null, $secretToken);
        if (strlen($chatId) <= 1) {
            $this->flashMessage('danger',
                $this->translator->translate('telegram.bot.api.chat.id.not.set'));
            return;
        }
        if ($failResult instanceof FailResult) {
            $this->flashFailResult($failResult);
            return;
        }
        $this->processTestMessage($chatId, $urlGenerator);
    }

    private function processTestMessage(string $chatId, UrlGenerator $urlGenerator): void
    {
        if ($this->telegramBotApi === null) {
            return;
        }
        $user = $this->telegramBotApi->getMe();
        if (!($user instanceof \Phptg\BotApi\Type\User)
                || $this->sR->getSetting('telegram_test_message_use') !== '1') {
            return;
        }
        $text = $this->translator->translate('telegram.bot.api.hello.world.test.message');
        $sendMessageResult = $this->telegramBotApi->sendMessage(
            $chatId, $text, null, null, null, null, null, null, null, null, null, null, null);
        if (!$sendMessageResult instanceof FailResult) {
            $this->flashMessage('success',
                $this->translator->translate('telegram.bot.api.hello.world.test.message.sent'));
        } else {
            $this->handleTestMessageFailure($sendMessageResult, $urlGenerator);
        }
    }

    private function handleTestMessageFailure(
        FailResult $result,
        UrlGenerator $urlGenerator,
    ): void {
        $this->flashMessage('danger',
            $this->translator->translate(
                'telegram.bot.api.hello.world.test.message.sent.not'));
        if ($result->description !== null) {
            $this->flashMessage('primary', 'Fail Result: ' . $result->description);
        }
        if ($result->errorCode !== null) {
            $match = match ($result->errorCode) {
                403 => 'Solution: 1. Send a Message to Your Bot: Open Telegram and'
                    . ' search for your bot by its username.'
                    . ' Start a chat with your bot and send any message to it.'
                    . ' 2. Open your browser and enter the following URL,'
                    . ' replacing YOUR_BOT_TOKEN with your bot token:'
                    . ' https://api.telegram.org/botYOUR_BOT_TOKEN/getUpdates'
                    . Button::deleteWebhook($urlGenerator, $this->translator),
                409 => Button::deleteWebhook($urlGenerator, $this->translator),
                default => $result->description ?? '',
            };
            $this->flashMessage('primary',
                'Fail Result: ' . (string) $result->errorCode . ' ' . $match);
        }
        $this->webService->getRedirectResponse('setting/tabIndex');
    }

    /**
     * @return array{0: string, 1: array<string, string>}
     */
    private function sendTelegramInvoiceToClient(
        string $token,
        string $inv_id,
        string $providerToken,
        string $currency,
    ): array {
        $inv = $this->iR->repoInvLoadedquery((int) $inv_id);
        if ($inv === null) {
            $this->flashMessage('danger', 'Invoice not found.');
            return ['inv/index', []];
        }
        $chatId = $inv->getClient()?->getClientTelegramChatId();
        if ($chatId === null || $chatId === '') {
            $this->flashMessage('danger',
                $this->translator->translate('telegram.invoice.client.chat.id.not.set'));
            return ['inv/view', ['id' => $inv_id]];
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
        return ['inv/view', ['id' => $inv_id]];
    }

    private function sendPdfDocumentToClient(string $token, Inv $inv): void
    {
        $chatId = $inv->getClient()?->getClientTelegramChatId();
        if ($chatId === null || $chatId === '') {
            $this->flashMessage('danger',
                $this->translator->translate('telegram.invoice.client.chat.id.not.set'));
            return;
        }
        $number     = $inv->getNumber() ?? (string) $inv->reqId();
        $archiveDir = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR
            . 'Uploads' . DIRECTORY_SEPARATOR . 'Archive';
        $globResult = glob($archiveDir . DIRECTORY_SEPARATOR . '*_' . $number . '.pdf');
        /** @var list<string> $matches */
        $matches = $globResult === false ? [] : $globResult;
        if ($matches === []) {
            $this->flashMessage('warning',
                $this->translator->translate('telegram.pdf.not.found'));
            return;
        }
        usort($matches, static fn(string $a, string $b): int => (int) filemtime($b) - (int) filemtime($a));
        $telegramHelper = new TelegramHelper($token, $this->logger);
        $result = $telegramHelper->sendInvoicePdf($chatId, $matches[0], 'Invoice #' . $number);
        if (!$result instanceof FailResult) {
            $this->flashMessage('success',
                $this->translator->translate('telegram.pdf.sent'));
        } else {
            $this->flashMessage('danger',
                'Telegram sendDocument failed: ' . ($result->description ?? ''));
        }
    }

    private function performRefundStars(string $token, string $payment_id, PR $pR): string
    {
        $payment = $pR->repoPaymentquery((int) $payment_id);
        if ($payment === null) {
            $this->flashMessage('danger', 'Payment not found.');
            return 'payment/index';
        }
        $note = $payment->getNote();
        if (!preg_match('/Telegram:\s*(\S+)\s*\|\s*tguid:(\d+)/', $note, $m)) {
            $this->flashMessage('warning',
                $this->translator->translate('telegram.stars.no.charge.id'));
            return 'payment/index';
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
        return 'payment/index';
    }

    private function performSetWebhook(
        string $token,
        UrlGenerator $urlGenerator,
    ): FailResult|\Phptg\BotApi\Type\Update\WebhookInfo
    {
        $telegramHelper = new TelegramHelper($token, $this->logger);
        $failResultSetWebhook = $telegramHelper->setWebhook(
            $urlGenerator, null, null, null, false, null);
        $failResultWebhookInfo = $telegramHelper->getWebhookInfo();
        if (!$failResultSetWebhook instanceof FailResult) {
            $this->flashMessage('success',
                $this->translator->translate('telegram.bot.api.webhook.setup'));
        } else {
            $this->flashMessage('success',
                $this->translator->translate('telegram.bot.api.webhook.setup.already'));
            $this->flashFailResult($failResultSetWebhook);
        }
        return $failResultWebhookInfo;
    }

    private function handleWebhookUpdate(string $token, string $body): void
    {
        $telegramHelper = new TelegramHelper($token, $this->logger);
        $update = TelegramHelper::pushToWebhook($body, $this->logger);
        if ($update->preCheckoutQuery !== null) {
            $telegramHelper->answerPreCheckoutQuery($update->preCheckoutQuery->id, true);
            return;
        }
        $successfulPayment = $update->message?->successfulPayment;
        if ($successfulPayment === null) {
            return;
        }
        try {
            /** @var array{inv_id?: int} $payload */
            $payload = Json::decode($successfulPayment->invoicePayload);
            $invId   = $payload['inv_id'] ?? 0;
            if ($invId > 0) {
                $methodId    = (int) ($this->sR->getSetting('telegram_payment_method_id') ?: 1);
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
    }
}
