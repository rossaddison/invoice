<?php

declare(strict_types=1);

namespace Tests\Testo\Bookkeeping\Application;

use App\Bookkeeping\Application\BookkeepingGatewayInterface;
use App\Bookkeeping\Application\BookkeepingResult;
use App\Bookkeeping\Application\BookkeepingService;
use App\Bookkeeping\Application\BookkeepingTransactionLookupResult;
use App\Bookkeeping\Application\BookkeepingTransactionRepositoryInterface;
use App\Bookkeeping\Domain\AccountRole;
use App\Bookkeeping\Domain\BookkeepingLine;
use App\Bookkeeping\Domain\BookkeepingTransaction;
use App\Bookkeeping\Domain\BookkeepingTransactionType;
use App\Bookkeeping\Domain\DebitCredit;
use DateTimeImmutable;
use Mockery as m;
use Testo\Assert;
use Testo\Test;

/**
 * A fresh set of mocks is built per test method rather than once in a
 * constructor -- Testo calls every test method on the same class
 * instance, so a constructor-built mock's expectations would leak
 * between tests (confirmed live for App\Widget\SubMenu's own Testo test
 * during this same session).
 */
#[Test]
final class BookkeepingServiceTest
{
    private function transaction(string $reference = 'INV-1024'): BookkeepingTransaction
    {
        return new BookkeepingTransaction(
            BookkeepingTransactionType::InvoiceIssued,
            $reference,
            new DateTimeImmutable('2026-09-18'),
            'GBP',
            [
                new BookkeepingLine(AccountRole::AccountsReceivable, DebitCredit::Debit, 120.00),
                new BookkeepingLine(AccountRole::Sales, DebitCredit::Credit, 100.00),
                new BookkeepingLine(AccountRole::VatOrTax, DebitCredit::Credit, 20.00),
            ],
        );
    }

    public function returnsAllFailedWithoutCallingTheGatewayWhenNotConfigured(): void
    {
        /** @var BookkeepingGatewayInterface&m\MockInterface $gateway */
        $gateway = m::mock(BookkeepingGatewayInterface::class);
        $gateway->shouldReceive('isConfigured')->andReturn(false);
        $gateway->shouldReceive('getDriverKey')->andReturn('xero');

        /** @var BookkeepingTransactionRepositoryInterface&m\MockInterface $repository */
        $repository = m::mock(BookkeepingTransactionRepositoryInterface::class);
        $repository->shouldReceive('findDueForExport')->andReturn([$this->transaction(), $this->transaction('INV-1025')]);

        $summary = (new BookkeepingService($gateway, $repository))->exportDueTransactions();

        Assert::same(0, $summary->exportedCount);
        Assert::same(2, $summary->failedCount);
        Assert::true(str_contains($summary->messages[0], 'xero is not configured.'));
    }

    public function marksAnAlreadyExistingTransactionAsExportedWithoutRecreatingIt(): void
    {
        /** @var BookkeepingGatewayInterface&m\MockInterface $gateway */
        $gateway = m::mock(BookkeepingGatewayInterface::class);
        $gateway->shouldReceive('isConfigured')->andReturn(true);
        $gateway->shouldReceive('getDriverKey')->andReturn('xero');
        $gateway->shouldReceive('getTransaction')
            ->with('INV-1024')
            ->andReturn(BookkeepingTransactionLookupResult::found('XERO-9001'));
        // Not creating a duplicate is the whole point of this test.
        $gateway->shouldNotReceive('createTransaction');

        $transaction = $this->transaction();

        /** @var BookkeepingTransactionRepositoryInterface&m\MockInterface $repository */
        $repository = m::mock(BookkeepingTransactionRepositoryInterface::class);
        $repository->shouldReceive('findDueForExport')->andReturn([$transaction]);
        $repository->shouldReceive('save')->once()->with($transaction);

        $summary = (new BookkeepingService($gateway, $repository))->exportDueTransactions();

        Assert::same(1, $summary->exportedCount);
        Assert::same(0, $summary->failedCount);
        Assert::true($transaction->isExported());
        Assert::same('xero', $transaction->getExportedProviderKey());
        Assert::same('XERO-9001', $transaction->getExportedProviderReference());
    }

    public function createsAndMarksExportedOnSuccess(): void
    {
        /** @var BookkeepingGatewayInterface&m\MockInterface $gateway */
        $gateway = m::mock(BookkeepingGatewayInterface::class);
        $gateway->shouldReceive('isConfigured')->andReturn(true);
        $gateway->shouldReceive('getDriverKey')->andReturn('xero');
        $gateway->shouldReceive('getTransaction')->andReturn(BookkeepingTransactionLookupResult::notFound());
        $gateway->shouldReceive('createTransaction')->andReturn(new BookkeepingResult(true, 'XERO-9002'));

        $transaction = $this->transaction();

        /** @var BookkeepingTransactionRepositoryInterface&m\MockInterface $repository */
        $repository = m::mock(BookkeepingTransactionRepositoryInterface::class);
        $repository->shouldReceive('findDueForExport')->andReturn([$transaction]);
        $repository->shouldReceive('save')->once()->with($transaction);

        $summary = (new BookkeepingService($gateway, $repository))->exportDueTransactions();

        Assert::same(1, $summary->exportedCount);
        Assert::same(0, $summary->failedCount);
        Assert::true($transaction->isExported());
        Assert::same('XERO-9002', $transaction->getExportedProviderReference());
    }

    public function recordsTheFailureMessageAndLeavesTheTransactionUnexported(): void
    {
        /** @var BookkeepingGatewayInterface&m\MockInterface $gateway */
        $gateway = m::mock(BookkeepingGatewayInterface::class);
        $gateway->shouldReceive('isConfigured')->andReturn(true);
        $gateway->shouldReceive('getDriverKey')->andReturn('xero');
        $gateway->shouldReceive('getTransaction')->andReturn(BookkeepingTransactionLookupResult::notFound());
        $gateway->shouldReceive('createTransaction')->andReturn(new BookkeepingResult(false, '', 'Xero API timeout'));

        $transaction = $this->transaction();

        /** @var BookkeepingTransactionRepositoryInterface&m\MockInterface $repository */
        $repository = m::mock(BookkeepingTransactionRepositoryInterface::class);
        $repository->shouldReceive('findDueForExport')->andReturn([$transaction]);
        $repository->shouldNotReceive('save');

        $summary = (new BookkeepingService($gateway, $repository))->exportDueTransactions();

        Assert::same(0, $summary->exportedCount);
        Assert::same(1, $summary->failedCount);
        Assert::same('INV-1024: Xero API timeout', $summary->messages[0]);
        Assert::false($transaction->isExported());
    }

    public function returnsAnEmptySummaryWhenNothingIsDue(): void
    {
        /** @var BookkeepingGatewayInterface&m\MockInterface $gateway */
        $gateway = m::mock(BookkeepingGatewayInterface::class);
        $gateway->shouldReceive('isConfigured')->andReturn(true);

        /** @var BookkeepingTransactionRepositoryInterface&m\MockInterface $repository */
        $repository = m::mock(BookkeepingTransactionRepositoryInterface::class);
        $repository->shouldReceive('findDueForExport')->andReturn([]);

        $summary = (new BookkeepingService($gateway, $repository))->exportDueTransactions();

        Assert::same(0, $summary->exportedCount);
        Assert::same(0, $summary->failedCount);
        Assert::same([], $summary->messages);
    }
}
