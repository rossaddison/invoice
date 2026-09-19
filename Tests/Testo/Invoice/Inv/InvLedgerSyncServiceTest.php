<?php

declare(strict_types=1);

namespace Tests\Testo\Invoice\Inv;

use App\Infrastructure\Persistence\Inv\Inv;
use App\Infrastructure\Persistence\InvAmount\InvAmount;
use App\Invoice\Inv\InvBookkeepingTransactionFactory;
use App\Invoice\Inv\InvLedgerSyncService;
use App\Invoice\Inv\InvRepository;
use Mockery as m;
use Testo\Assert;
use Testo\Test;

#[Test]
final class InvLedgerSyncServiceTest
{
    private function invoice(int $id, int $status, int $creditParent = 0): Inv
    {
        $inv = new Inv(status_id: $status, creditinvoice_parent_id: $creditParent);
        $inv->setId($id);
        return $inv;
    }

    /**
     * @param list<Inv> $invoices
     * @return array{0: InvLedgerSyncService, 1: InvBookkeepingTransactionFactory&m\MockInterface}
     */
    private function make(array $invoices): array
    {
        /** @var InvRepository&m\MockInterface $repo */
        $repo = m::mock(InvRepository::class);
        $repo->shouldReceive('repoNonDraftLoadedInvAmount')->andReturn($invoices);
        /** @var InvBookkeepingTransactionFactory&m\MockInterface $factory */
        $factory = m::mock(InvBookkeepingTransactionFactory::class);

        return [new InvLedgerSyncService($repo, $factory), $factory];
    }

    public function issuedAndViewedInvoicesPostOnlyTheInvoiceEntry(): void
    {
        [$service, $factory] = $this->make([$this->invoice(1, 2), $this->invoice(2, 3)]);
        $factory->shouldReceive('createForIssuedInvoice')->twice();
        $factory->shouldReceive('createForPaymentReceived')->never();

        Assert::same($service->sync(), 2);
    }

    public function aPaidInvoicePostsTheInvoiceAndPaymentEntries(): void
    {
        [$service, $factory] = $this->make([$this->invoice(1, 4)]);
        $factory->shouldReceive('createForIssuedInvoice')->once();
        $factory->shouldReceive('createForPaymentReceived')->once();

        $service->sync();
    }

    public function aVoidInvoicePostsOnlyTheReversal(): void
    {
        [$service, $factory] = $this->make([$this->invoice(1, 14)]);
        $factory->shouldReceive('createForVoidedInvoice')->once();
        $factory->shouldReceive('createForIssuedInvoice')->never();

        $service->sync();
    }

    public function aCreditNotePostsItsOwnEntryNotAnInvoiceEntry(): void
    {
        [$service, $factory] = $this->make([$this->invoice(1, 12), $this->invoice(2, 2, 77)]);
        $factory->shouldReceive('createForCreditNote')->twice();
        $factory->shouldReceive('createForIssuedInvoice')->never();

        $service->sync();
    }
}
