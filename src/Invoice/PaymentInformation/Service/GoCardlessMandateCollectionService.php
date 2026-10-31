<?php

declare(strict_types=1);

namespace App\Invoice\PaymentInformation\Service;

use App\Infrastructure\Persistence\Inv\Inv;
use App\Infrastructure\Persistence\InvAmount\InvAmount;
use App\Invoice\Inv\InvRepository as iR;
use App\Invoice\InvAmount\InvAmountRepository as iaR;
use App\Invoice\Setting\SettingRepository as sR;
use DateTimeImmutable;
use Psr\Log\LoggerInterface;

final class GoCardlessMandateCollectionService
{
    private const int MINIMUM_LEAD_DAYS = 5;

    public function __construct(
        private readonly GoCardlessPaymentService $paymentService,
        private readonly iR $iR,
        private readonly iaR $iaR,
        private readonly sR $sR,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function scheduleFromBillingRequest(string $billingRequestId): ?Inv
    {
        $mandate = $this->paymentService->getCompletedBillingRequest($billingRequestId);

        if ($mandate === null) {
            return null;
        }

        $urlKey = $mandate['invoice_url_key'];
        $invoice = $this->iR->repoUrlKeyGuestLoaded($urlKey);

        if ($invoice === null) {
            $this->logger->warning(
                'GoCardless: invoice not found for Billing Request.',
                [
                    'billing_request_id' => $billingRequestId,
                    'invoice_url_key' => $urlKey,
                ],
            );

            return null;
        }

        if ($invoice->getDirectDebitDate() !== null) {
            return $invoice;
        }

        /** @var InvAmount $invoiceAmountRecord */
        $invoiceAmountRecord = $this->iaR->repoInvquery($invoice->reqId());

        $balance = $invoiceAmountRecord->getBalance() ?? 0.00;

        if ($balance <= 0.00) {
            return $invoice;
        }

        $chargeDate = $this->resolveChargeDate($invoice->getDateDue());
        $currency = $this->sR->getSetting('currency_code') ?: 'GBP';

        $idempotencyKey = 'invoice-' . substr(
            hash('sha256', $urlKey),
            0,
            48,
        );

        $payment = $this->paymentService->scheduleDirectDebitPayment(
            $mandate['mandate_id'],
            $balance,
            $currency,
            $chargeDate,
            ['invoice_url_key' => $urlKey],
            $idempotencyKey,
        );

        $invoice->setDirectDebitDate(
            new DateTimeImmutable($payment['charge_date']),
        );
        $invoice->setPaymentMethod(6);

        $this->iR->save($invoice);

        return $invoice;
    }

    private function resolveChargeDate(DateTimeImmutable $dateDue): DateTimeImmutable
    {
        $earliest = (new DateTimeImmutable())->modify(
            '+' . self::MINIMUM_LEAD_DAYS . ' days',
        );

        return $dateDue > $earliest ? $dateDue : $earliest;
    }
}
