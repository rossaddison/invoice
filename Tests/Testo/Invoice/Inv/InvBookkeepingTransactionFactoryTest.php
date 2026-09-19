<?php

declare(strict_types=1);

namespace Tests\Testo\Invoice\Inv;

use App\Bookkeeping\Application\BookkeepingTransactionRepositoryInterface;
use App\Bookkeeping\Domain\AccountRole;
use App\Bookkeeping\Domain\BookkeepingLine;
use App\Bookkeeping\Domain\BookkeepingTransaction;
use App\Bookkeeping\Domain\BookkeepingTransactionType;
use App\Bookkeeping\Domain\DebitCredit;
use App\Infrastructure\Persistence\Inv\Inv;
use App\Infrastructure\Persistence\InvAmount\InvAmount;
use App\Invoice\Inv\InvBookkeepingTransactionFactory;
use App\Invoice\Setting\SettingRepository;
use Mockery as m;
use Testo\Assert;
use Testo\Test;

/**
 * Covers InvBookkeepingTransactionFactory — the step that turns a
 * just-settled Inv into the BookkeepingTransactions the Bookkeeping
 * module needs, called from
 * InvPaymentSettlementService::markInvoicePaidAndAdjustStock() (see
 * InvPaymentSettlementServiceTest for the wiring-level coverage).
 */
#[Test]
final class InvBookkeepingTransactionFactoryTest
{
    /**
     * @param array<string, BookkeepingTransaction> $saved
     */
    private function requireSaved(array $saved, BookkeepingTransactionType $type): BookkeepingTransaction
    {
        return $saved[$type->value] ?? throw new \LogicException(
            'Expected a saved ' . $type->value . ' transaction, none was captured.'
        );
    }

    private function makeInvoice(int $id, ?string $number): Inv
    {
        $invoice = new Inv();
        $invoice->setId($id);
        if ($number !== null) {
            $invoice->setNumber($number);
        }
        return $invoice;
    }

    private function makeFactory(
        BookkeepingTransactionRepositoryInterface $repository,
        string $currency = 'GBP',
    ): InvBookkeepingTransactionFactory {
        /** @var SettingRepository&m\MockInterface $settingRepository */
        $settingRepository = m::mock(SettingRepository::class);
        $settingRepository->shouldReceive('getSetting')->with('currency_code_from')->andReturn($currency);

        return new InvBookkeepingTransactionFactory($repository, $settingRepository);
    }

    public function createsBalancedInvoiceIssuedAndPaymentReceivedTransactionsForAPaidInvoice(): void
    {
        $invoice = $this->makeInvoice(201, 'INV-201');
        $invoiceAmountRecord = new InvAmount(inv_id: 201, item_tax_total: 20.00, tax_total: 20.00, total: 120.00);

        /** @var BookkeepingTransactionRepositoryInterface&m\MockInterface $repository */
        $repository = m::mock(BookkeepingTransactionRepositoryInterface::class);
        $repository->shouldReceive('findByReference')->with('INV-201-invoice')->andReturn(null);
        $repository->shouldReceive('findByReference')->with('INV-201-payment')->andReturn(null);

        /** @var array<string, BookkeepingTransaction> $saved */
        $saved = [];
        $repository->shouldReceive('save')
            ->twice()
            ->with(m::type(BookkeepingTransaction::class))
            ->andReturnUsing(static function (BookkeepingTransaction $transaction) use (&$saved): void {
                $saved[$transaction->getType()->value] = $transaction;
            });

        $factory = $this->makeFactory($repository);
        $factory->createForPaidInvoice($invoice, $invoiceAmountRecord);

        Assert::count($saved, 2);

        $invoiceIssued = $this->requireSaved($saved, BookkeepingTransactionType::InvoiceIssued);
        $paymentReceived = $this->requireSaved($saved, BookkeepingTransactionType::PaymentReceived);

        Assert::same(BookkeepingTransactionType::InvoiceIssued, $invoiceIssued->getType());
        Assert::same('INV-201-invoice', $invoiceIssued->getReference());
        Assert::same('GBP', $invoiceIssued->getCurrency());
        Assert::same(201, $invoiceIssued->getSourceInvId());
        Assert::count($invoiceIssued->getLines(), 3);
        Assert::same(AccountRole::AccountsReceivable, $invoiceIssued->getLines()[0]->account);
        Assert::same(DebitCredit::Debit, $invoiceIssued->getLines()[0]->direction);
        Assert::same(120.00, $invoiceIssued->getLines()[0]->amount);
        Assert::same(AccountRole::Sales, $invoiceIssued->getLines()[1]->account);
        Assert::same(100.00, $invoiceIssued->getLines()[1]->amount);
        Assert::same(AccountRole::VatOrTax, $invoiceIssued->getLines()[2]->account);
        Assert::same(20.00, $invoiceIssued->getLines()[2]->amount);

        Assert::same(BookkeepingTransactionType::PaymentReceived, $paymentReceived->getType());
        Assert::same('INV-201-payment', $paymentReceived->getReference());
        Assert::count($paymentReceived->getLines(), 2);
        Assert::same(AccountRole::Bank, $paymentReceived->getLines()[0]->account);
        Assert::same(DebitCredit::Debit, $paymentReceived->getLines()[0]->direction);
        Assert::same(120.00, $paymentReceived->getLines()[0]->amount);
        Assert::same(AccountRole::AccountsReceivable, $paymentReceived->getLines()[1]->account);
        Assert::same(DebitCredit::Credit, $paymentReceived->getLines()[1]->direction);
    }

    public function omitsTheSalesLineWhenTheEntireTotalIsTax(): void
    {
        $invoice = $this->makeInvoice(202, 'INV-202');
        $invoiceAmountRecord = new InvAmount(inv_id: 202, tax_total: 50.00, total: 50.00);

        /** @var BookkeepingTransactionRepositoryInterface&m\MockInterface $repository */
        $repository = m::mock(BookkeepingTransactionRepositoryInterface::class);
        $repository->shouldReceive('findByReference')->andReturn(null);

        /** @var array<string, BookkeepingTransaction> $saved */
        $saved = [];
        $repository->shouldReceive('save')
            ->twice()
            ->andReturnUsing(static function (BookkeepingTransaction $transaction) use (&$saved): void {
                $saved[$transaction->getType()->value] = $transaction;
            });

        $factory = $this->makeFactory($repository);
        $factory->createForPaidInvoice($invoice, $invoiceAmountRecord);

        $invoiceIssued = $this->requireSaved($saved, BookkeepingTransactionType::InvoiceIssued);
        Assert::count($invoiceIssued->getLines(), 2);
        Assert::same(AccountRole::AccountsReceivable, $invoiceIssued->getLines()[0]->account);
        Assert::same(AccountRole::VatOrTax, $invoiceIssued->getLines()[1]->account);
    }

    public function fallsBackToAHashInvoiceIdWhenTheInvoiceHasNoNumber(): void
    {
        $invoice = $this->makeInvoice(203, null);
        $invoiceAmountRecord = new InvAmount(inv_id: 203, total: 40.00);

        /** @var BookkeepingTransactionRepositoryInterface&m\MockInterface $repository */
        $repository = m::mock(BookkeepingTransactionRepositoryInterface::class);
        $repository->shouldReceive('findByReference')->with('#203-invoice')->once()->andReturn(null);
        $repository->shouldReceive('findByReference')->with('#203-payment')->once()->andReturn(null);
        $repository->shouldReceive('save')->twice();

        $factory = $this->makeFactory($repository);
        $factory->createForPaidInvoice($invoice, $invoiceAmountRecord);
    }

    public function isIdempotentWhenBothTransactionsAlreadyExist(): void
    {
        $invoice = $this->makeInvoice(204, 'INV-204');
        $invoiceAmountRecord = new InvAmount(inv_id: 204, total: 60.00);

        /** @var BookkeepingTransaction&m\MockInterface $existingInvoiceIssued */
        $existingInvoiceIssued = m::mock(BookkeepingTransaction::class);
        /** @var BookkeepingTransaction&m\MockInterface $existingPaymentReceived */
        $existingPaymentReceived = m::mock(BookkeepingTransaction::class);

        /** @var BookkeepingTransactionRepositoryInterface&m\MockInterface $repository */
        $repository = m::mock(BookkeepingTransactionRepositoryInterface::class);
        $repository->shouldReceive('findByReference')->with('INV-204-invoice')->andReturn($existingInvoiceIssued);
        $repository->shouldReceive('findByReference')->with('INV-204-payment')->andReturn($existingPaymentReceived);
        $repository->shouldNotReceive('save');

        $factory = $this->makeFactory($repository);
        $factory->createForPaidInvoice($invoice, $invoiceAmountRecord);
    }

    public function doesNothingWhenTheInvoiceTotalIsZeroOrLess(): void
    {
        $invoice = $this->makeInvoice(205, 'INV-205');
        $invoiceAmountRecord = new InvAmount(inv_id: 205, total: 0.00);

        /** @var BookkeepingTransactionRepositoryInterface&m\MockInterface $repository */
        $repository = m::mock(BookkeepingTransactionRepositoryInterface::class);
        $repository->shouldNotReceive('findByReference');
        $repository->shouldNotReceive('save');

        $factory = $this->makeFactory($repository);
        $factory->createForPaidInvoice($invoice, $invoiceAmountRecord);
    }

    public function createsABalancedRefundTransactionReversingSalesAndTaxAgainstBank(): void
    {
        $invoice = $this->makeInvoice(301, 'INV-301');
        $invoiceAmountRecord = new InvAmount(inv_id: 301, item_tax_total: 20.00, tax_total: 20.00, total: 120.00);

        /** @var BookkeepingTransactionRepositoryInterface&m\MockInterface $repository */
        $repository = m::mock(BookkeepingTransactionRepositoryInterface::class);
        $repository->shouldReceive('findByReference')->with('INV-301-refund')->andReturn(null);

        /** @var array<string, BookkeepingTransaction> $saved */
        $saved = [];
        $repository->shouldReceive('save')
            ->once()
            ->with(m::type(BookkeepingTransaction::class))
            ->andReturnUsing(static function (BookkeepingTransaction $transaction) use (&$saved): void {
                $saved[$transaction->getType()->value] = $transaction;
            });

        $this->makeFactory($repository)->createForRefundedInvoice($invoice, $invoiceAmountRecord, 120.00);

        $refund = $this->requireSaved($saved, BookkeepingTransactionType::Refund);
        Assert::same($refund->getReference(), 'INV-301-refund');
        Assert::same($refund->getSourceInvId(), 301);
        Assert::count($refund->getLines(), 5);
        Assert::same($refund->getLines()[2]->account, AccountRole::AccountsReceivable);
        Assert::same($refund->getLines()[2]->direction, DebitCredit::Credit);
        Assert::same($refund->getLines()[3]->account, AccountRole::AccountsReceivable);
        Assert::same($refund->getLines()[3]->direction, DebitCredit::Debit);
        Assert::same($refund->getLines()[0]->account, AccountRole::Sales);
        Assert::same($refund->getLines()[0]->direction, DebitCredit::Debit);
        Assert::same($refund->getLines()[0]->amount, 100.00);
        Assert::same($refund->getLines()[1]->account, AccountRole::VatOrTax);
        Assert::same($refund->getLines()[1]->direction, DebitCredit::Debit);
        Assert::same($refund->getLines()[1]->amount, 20.00);
        Assert::same($refund->getLines()[4]->account, AccountRole::Bank);
        Assert::same($refund->getLines()[4]->direction, DebitCredit::Credit);
        Assert::same($refund->getLines()[4]->amount, 120.00);
    }

    public function refundIsIdempotentWhenTheRefundTransactionAlreadyExists(): void
    {
        $invoice = $this->makeInvoice(302, 'INV-302');
        $invoiceAmountRecord = new InvAmount(inv_id: 302, tax_total: 0.00, total: 50.00);

        /** @var BookkeepingTransactionRepositoryInterface&m\MockInterface $repository */
        $repository = m::mock(BookkeepingTransactionRepositoryInterface::class);
        $repository->shouldReceive('findByReference')->with('INV-302-refund')->andReturn(
            new BookkeepingTransaction(
                BookkeepingTransactionType::Refund,
                'INV-302-refund',
                new \DateTimeImmutable(),
                'GBP',
                [
                    new BookkeepingLine(AccountRole::Sales, DebitCredit::Debit, 50.00),
                    new BookkeepingLine(AccountRole::Bank, DebitCredit::Credit, 50.00),
                ],
                302,
            ),
        );
        $repository->shouldReceive('save')->never();

        $this->makeFactory($repository)->createForRefundedInvoice($invoice, $invoiceAmountRecord, 50.00);
    }

    public function refundDoesNothingForAZeroRefundAmount(): void
    {
        $invoice = $this->makeInvoice(303, 'INV-303');
        $invoiceAmountRecord = new InvAmount(inv_id: 303, tax_total: 0.00, total: 50.00);

        /** @var BookkeepingTransactionRepositoryInterface&m\MockInterface $repository */
        $repository = m::mock(BookkeepingTransactionRepositoryInterface::class);
        $repository->shouldReceive('save')->never();

        $this->makeFactory($repository)->createForRefundedInvoice($invoice, $invoiceAmountRecord, 0.00);
    }
}
