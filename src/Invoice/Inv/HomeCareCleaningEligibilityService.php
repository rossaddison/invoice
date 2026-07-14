<?php

declare(strict_types=1);

namespace App\Invoice\Inv;

use App\Infrastructure\Persistence\Inv\Inv;
use App\Invoice\Payment\PaymentRepositoryInterface;

final readonly class HomeCareCleaningEligibilityService
{
    public function __construct(
        private InvRepositoryInterface $invRepository,
        private PaymentRepositoryInterface $paymentRepository,
    ) {
    }

    /**
     * Decides whether a homecare-cleaning QR scan should trigger a new
     * invoice for this client, and if so, which invoice's items/amounts
     * should be copied into the new one.
     *
     * Eligible only when: the client has at least one invoice on file, their
     * most recently paid invoice has a payment date on record, and no
     * invoice at all (paid or not) exists dated after that payment date.
     */
    public function findInvoiceToCopyIfEligible(int $clientId): ?Inv
    {
        if ($this->invRepository->repoClientInvoiceCountquery($clientId) === 0) {
            return null;
        }

        $lastPaid = $this->invRepository->repoClientLatestPaidInvoicequery($clientId);
        if ($lastPaid === null) {
            return null;
        }

        $lastPaidDate = $this->paymentRepository->repoLatestPaymentDateForInvquery($lastPaid->reqId());
        if ($lastPaidDate === null) {
            return null;
        }

        if ($this->invRepository->repoClientInvoiceCountAfterDatequery($clientId, $lastPaidDate) > 0) {
            return null;
        }

        return $lastPaid;
    }
}
