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
use DateTimeImmutable;
use Mockery as m;
use Testo\Assert;
use Testo\Test;

/**
 * Issued / payment / void / credit-note entries built by
 * InvBookkeepingTransactionFactory for the ledger sync.
 */
#[Test]
final class InvLedgerEntriesTest
{
    private function makeInvoice(int $id, string $number): Inv
    {
        $invoice = new Inv();
        $invoice->setId($id);
        $invoice->setNumber($number);
        return $invoice;
    }

    /**
     * @param array<string, BookkeepingTransaction> $saved
     * @param list<string> $existing references that already exist
     */
    private function makeFactory(array &$saved, array $existing = []): InvBookkeepingTransactionFactory
    {
        /** @var BookkeepingTransactionRepositoryInterface&m\MockInterface $repository */
        $repository = m::mock(BookkeepingTransactionRepositoryInterface::class);
        $repository->shouldReceive('findByReference')->andReturnUsing(
            static fn(string $ref): ?BookkeepingTransaction => in_array($ref, $existing, true)
                ? new BookkeepingTransaction(
                    BookkeepingTransactionType::InvoiceIssued,
                    $ref,
                    new DateTimeImmutable(),
                    'GBP',
                    [
                        new BookkeepingLine(AccountRole::AccountsReceivable, DebitCredit::Debit, 1.00),
                        new BookkeepingLine(AccountRole::Sales, DebitCredit::Credit, 1.00),
                    ],
                )
                : null,
        );
        $repository->shouldReceive('save')->andReturnUsing(
            static function (BookkeepingTransaction $transaction) use (&$saved): void {
                $saved[$transaction->getReference()] = $transaction;
            },
        );

        /** @var SettingRepository&m\MockInterface $settings */
        $settings = m::mock(SettingRepository::class);
        $settings->shouldReceive('getSetting')->with('currency_code_from')->andReturn('GBP');

        return new InvBookkeepingTransactionFactory($repository, $settings);
    }

    public function issuedInvoiceEntryIsDatedByTheInvoiceDate(): void
    {
        $invoice = $this->makeInvoice(401, 'INV-401');
        $amount = new InvAmount(inv_id: 401, tax_total: 20.00, total: 120.00);
        $saved = [];

        $this->makeFactory($saved)->createForIssuedInvoice($invoice, $amount);

        Assert::count($saved, 1);
        Assert::same($saved['INV-401-invoice']->getType(), BookkeepingTransactionType::InvoiceIssued);
        Assert::same(
            $saved['INV-401-invoice']->getDate()->format('Y-m-d'),
            $invoice->getDateCreated()->format('Y-m-d'),
        );
    }

    public function paymentReceivedIsCreatedOnItsOwn(): void
    {
        $invoice = $this->makeInvoice(402, 'INV-402');
        $amount = new InvAmount(inv_id: 402, tax_total: 0.00, total: 50.00);
        $saved = [];

        $this->makeFactory($saved)->createForPaymentReceived($invoice, $amount);

        Assert::count($saved, 1);
        Assert::same($saved['INV-402-payment']->getType(), BookkeepingTransactionType::PaymentReceived);
    }

    public function voidReversesTheIssuedEntryWithBalancedLines(): void
    {
        $invoice = $this->makeInvoice(403, 'INV-403');
        $amount = new InvAmount(inv_id: 403, tax_total: 20.00, total: 120.00);
        $saved = [];

        $this->makeFactory($saved, ['INV-403-invoice'])->createForVoidedInvoice($invoice, $amount);

        $void = $saved['INV-403-void'];
        Assert::same($void->getType(), BookkeepingTransactionType::Void);
        Assert::count($void->getLines(), 3);
        Assert::same($void->getLines()[0]->account, AccountRole::Sales);
        Assert::same($void->getLines()[0]->direction, DebitCredit::Debit);
        Assert::same($void->getLines()[1]->account, AccountRole::VatOrTax);
        Assert::same($void->getLines()[2]->account, AccountRole::AccountsReceivable);
        Assert::same($void->getLines()[2]->direction, DebitCredit::Credit);
        Assert::same($void->getLines()[2]->amount, 120.00);
    }

    public function voidDoesNothingWhenTheInvoiceWasNeverPosted(): void
    {
        $invoice = $this->makeInvoice(404, 'INV-404');
        $amount = new InvAmount(inv_id: 404, tax_total: 0.00, total: 50.00);
        $saved = [];

        $this->makeFactory($saved)->createForVoidedInvoice($invoice, $amount);

        Assert::count($saved, 0);
    }

    public function creditNoteReducesReceivablesUsingTheAbsoluteAmounts(): void
    {
        $invoice = $this->makeInvoice(405, 'INV-405');
        $amount = new InvAmount(inv_id: 405, tax_total: -20.00, total: -120.00);
        $saved = [];

        $factory = $this->makeFactory($saved);
        $factory->createForCreditNote($invoice, $amount);
        $factory->createForCreditNote($invoice, $amount);

        Assert::count($saved, 1);
        $credit = $saved['INV-405-creditnote'];
        Assert::same($credit->getType(), BookkeepingTransactionType::CreditNote);
        Assert::same($credit->getLines()[0]->amount, 100.00);
        Assert::same($credit->getLines()[1]->amount, 20.00);
        Assert::same($credit->getLines()[2]->account, AccountRole::AccountsReceivable);
        Assert::same($credit->getLines()[2]->amount, 120.00);
    }
}
