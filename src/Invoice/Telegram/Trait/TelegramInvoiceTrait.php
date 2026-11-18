<?php

declare(strict_types=1);

namespace App\Invoice\Telegram\Trait;

use App\Infrastructure\Persistence\Payment\Payment;
use App\Invoice\Helpers\Telegram\TelegramHelper;
use App\Invoice\Telegram\TelegramUpdateHandler;
use JsonException;
use Phptg\BotApi\FailResult;
use Yiisoft\Json\Json;

trait TelegramInvoiceTrait
{
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
            $this->flashMessage(
                'danger',
                $this->translator->translate('telegram.invoice.client.chat.id.not.set')
            );
            return ['inv/view', ['id' => $inv_id]];
        }
        $telegramHelper = new TelegramHelper($token, $this->logger);
        $result = $telegramHelper->sendTelegramInvoice($chatId, $inv, $currency, $providerToken);
        if (!$result instanceof FailResult) {
            $this->flashMessage(
                'success',
                $this->translator->translate('telegram.invoice.sent')
            );
        } else {
            $this->flashMessage(
                'danger',
                'Telegram sendInvoice failed: ' . ($result->description ?? '')
            );
            if ($result->errorCode !== null) {
                $this->flashMessage('primary', 'Error code: ' . (string) $result->errorCode);
            }
        }
        return ['inv/view', ['id' => $inv_id]];
    }

    /**
     * @param bool $commandsTrusted whether the webhook secret token is configured and matched. The
     *                              account-linking commands change who receives messages, so they are
     *                              only acted on when it is: with no secret set, anyone who knows the
     *                              webhook address could post an update.
     */
    private function handleWebhookUpdate(
        string $token,
        string $body,
        TelegramUpdateHandler $updateHandler,
        bool $commandsTrusted,
    ): void {
        $telegramHelper = new TelegramHelper($token, $this->logger);
        $update = TelegramHelper::pushToWebhook($body, $this->logger);
        if ($commandsTrusted) {
            $updateHandler->handle($update, $telegramHelper);
        }
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
