<?php

declare(strict_types=1);

namespace App\Bookkeeping\Application;

use DateTimeImmutable;

/**
 * Orchestrates exporting already-built, already-persisted
 * BookkeepingTransactions to whichever provider this app is configured
 * for. Which concrete BookkeepingGatewayInterface implementation that is
 * (currently only QuickBooksGateway) is a DI-config concern
 * (config/common/di/), not application logic -- exactly one gateway is
 * injected here rather than this service iterating a provider list
 * itself, matching how Yii3 DI binds a single interface to one
 * implementation by default; swapping providers is a config change, not
 * a code change, which is the actual payoff of the port existing at all.
 *
 * Deliberately does NOT build BookkeepingTransactions from Inv/Payment
 * data itself -- that's App\Invoice\Inv\InvBookkeepingTransactionFactory's
 * job, called from InvPaymentSettlementService (the same place this app
 * already writes StockMovement rows when an Inv reaches status_id 4).
 * This service only knows how to export whatever the repository already
 * has queued.
 */
final class BookkeepingService
{
    public function __construct(
        private readonly BookkeepingGatewayInterface $gateway,
        private readonly BookkeepingTransactionRepositoryInterface $repository,
    ) {
    }

    public function exportDueTransactions(): BookkeepingExportSummary
    {
        $due = $this->repository->findDueForExport();

        if (!$this->gateway->isConfigured()) {
            return new BookkeepingExportSummary(0, count($due), [
                $this->gateway->getDriverKey() . ' is not configured.',
            ]);
        }

        $exportedCount = 0;
        $failedCount = 0;
        $messages = [];

        foreach ($due as $transaction) {
            // Checked first, not just as a createTransaction() fallback:
            // a previous run's create call may have succeeded at the
            // provider while its response was lost to us (network
            // timeout), which findDueForExport() alone can't know --
            // only asking the provider directly can confirm it, and
            // re-posting on that guess would create a duplicate.
            $existing = $this->gateway->getTransaction($transaction->getReference());
            if ($existing->found && $existing->providerReference !== null) {
                $transaction->markExported($this->gateway->getDriverKey(), $existing->providerReference, new DateTimeImmutable());
                $this->repository->save($transaction);
                $exportedCount++;
                continue;
            }

            $result = $this->gateway->createTransaction($transaction);
            if (!$result->success) {
                $messages[] = $transaction->getReference() . ': ' . $result->message;
                $failedCount++;
                continue;
            }

            $transaction->markExported($this->gateway->getDriverKey(), $result->providerReference, new DateTimeImmutable());
            $this->repository->save($transaction);
            $exportedCount++;
        }

        return new BookkeepingExportSummary($exportedCount, $failedCount, $messages);
    }
}
