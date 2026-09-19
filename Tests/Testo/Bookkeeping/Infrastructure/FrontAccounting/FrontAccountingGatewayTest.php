<?php

declare(strict_types=1);

namespace Tests\Testo\Bookkeeping\Infrastructure\FrontAccounting;

use App\Bookkeeping\Application\BookkeepingDocument;
use App\Bookkeeping\Application\BookkeepingDocumentSourceInterface;
use App\Bookkeeping\Application\BookkeepingTransactionRepositoryInterface;
use App\Bookkeeping\Domain\AccountRole;
use App\Bookkeeping\Domain\BookkeepingLine;
use App\Bookkeeping\Domain\BookkeepingTransaction;
use App\Bookkeeping\Domain\BookkeepingTransactionType;
use App\Bookkeeping\Domain\DebitCredit;
use App\Bookkeeping\Infrastructure\FrontAccounting\FrontAccountingGateway;
use App\Invoice\Setting\SettingRepository;
use DateTimeImmutable;
use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Request as Psr7Request;
use GuzzleHttp\Psr7\Response;
use Mockery as m;
use Psr\Http\Message\RequestInterface;
use Psr\Log\LoggerInterface;
use Testo\Assert;
use Testo\Test;

/**
 * FrontAccountingGateway against a mocked Guzzle handler: no real network.
 * The live behaviour of the API these calls target was checked by hand
 * against FrontAccounting 2.4.20 (see the FrontAccountingSimpleAPI PRs).
 */
#[Test]
final class FrontAccountingGatewayTest
{
    private ?\ArrayObject $history = null;

    /**
     * @param array<string, string> $overrides
     */
    private function makeSettings(array $overrides = []): SettingRepository
    {
        $values = array_merge([
            'bookkeeping_frontaccounting_base_url' => 'http://fa.test/modules/api',
            'bookkeeping_frontaccounting_company' => '0',
            'bookkeeping_frontaccounting_username' => 'admin',
            'bookkeeping_frontaccounting_password' => 'enc:secret',
            'bookkeeping_frontaccounting_bank_account' => '1',
            'bookkeeping_frontaccounting_stock_id' => '301',
        ], $overrides);

        /** @var SettingRepository&m\MockInterface $settings */
        $settings = m::mock(SettingRepository::class);
        $settings->shouldReceive('getSetting')->andReturnUsing(
            static fn(string $key): string => $values[$key] ?? '',
        );
        $settings->shouldReceive('decode')->andReturnUsing(
            static fn(string $data): string => str_starts_with($data, 'enc:') ? substr($data, 4) : $data,
        );

        return $settings;
    }

    private function makeDocuments(?BookkeepingDocument $document): BookkeepingDocumentSourceInterface
    {
        /** @var BookkeepingDocumentSourceInterface&m\MockInterface $documents */
        $documents = m::mock(BookkeepingDocumentSourceInterface::class);
        $documents->shouldReceive('forInvoice')->andReturn($document);

        return $documents;
    }

    private function aDocument(): BookkeepingDocument
    {
        return new BookkeepingDocument('Y3I-7', 'Acme Ltd', '1 High St, Leeds', 'INV-9', new DateTimeImmutable('2026-10-19'));
    }

    private function exportedInvoiceRepository(?string $fromFaNumber): BookkeepingTransactionRepositoryInterface
    {
        /** @var BookkeepingTransactionRepositoryInterface&m\MockInterface $repository */
        $repository = m::mock(BookkeepingTransactionRepositoryInterface::class);
        $invoiceRow = null;
        if ($fromFaNumber !== null) {
            $invoiceRow = new BookkeepingTransaction(
                BookkeepingTransactionType::InvoiceIssued,
                'INV-9-invoice',
                new DateTimeImmutable('2026-09-19'),
                'GBP',
                [
                    new BookkeepingLine(AccountRole::AccountsReceivable, DebitCredit::Debit, 1.00),
                    new BookkeepingLine(AccountRole::Sales, DebitCredit::Credit, 1.00),
                ],
                9,
            );
            $invoiceRow->markExported('frontaccounting', $fromFaNumber, new DateTimeImmutable());
        }
        $repository->shouldReceive('findByReference')->with('INV-9-invoice')->andReturn($invoiceRow);

        return $repository;
    }

    private function makeGateway(
        MockHandler $mock,
        ?BookkeepingDocument $document = null,
        ?SettingRepository $settings = null,
        ?BookkeepingTransactionRepositoryInterface $repository = null,
    ): FrontAccountingGateway {
        $log = new \ArrayObject();
        $this->history = $log;
        $stack = HandlerStack::create($mock);
        $stack->push(Middleware::history($log));

        return new FrontAccountingGateway(
            $settings ?? $this->makeSettings(),
            $this->makeLogger(),
            $this->makeDocuments($document ?? $this->aDocument()),
            $repository ?? $this->exportedInvoiceRepository('8'),
            new HttpClient(['handler' => $stack]),
        );
    }

    private function makeLogger(): LoggerInterface
    {
        /** @var LoggerInterface&m\MockInterface $logger */
        $logger = m::spy(LoggerInterface::class);

        return $logger;
    }

    private function json(mixed $data, int $status = 200): Response
    {
        return new Response($status, [], json_encode($data, JSON_THROW_ON_ERROR));
    }

    private function invoiceTransaction(): BookkeepingTransaction
    {
        return new BookkeepingTransaction(
            BookkeepingTransactionType::InvoiceIssued,
            'INV-9-invoice',
            new DateTimeImmutable('2026-09-19'),
            'GBP',
            [
                new BookkeepingLine(AccountRole::AccountsReceivable, DebitCredit::Debit, 120.00),
                new BookkeepingLine(AccountRole::Sales, DebitCredit::Credit, 100.00),
                new BookkeepingLine(AccountRole::VatOrTax, DebitCredit::Credit, 20.00),
            ],
            9,
        );
    }

    private function requestAt(int $index): RequestInterface
    {
        /** @var array{request: RequestInterface} $entry */
        $entry = ($this->history ?? throw new \LogicException('No gateway was built.'))[$index];
        return $entry['request'];
    }

    private function lastRequest(): RequestInterface
    {
        return $this->requestAt(count($this->history ?? []) - 1);
    }

    private function formAt(int $index): FormReader
    {
        return FormReader::fromBody((string) $this->requestAt($index)->getBody());
    }

    public function isConfiguredOnlyWhenEveryRequiredSettingIsPresent(): void
    {
        Assert::true($this->makeGateway(new MockHandler([]))->isConfigured());
        Assert::false($this->makeGateway(new MockHandler([]), null, $this->makeSettings([
            'bookkeeping_frontaccounting_bank_account' => '',
        ]))->isConfigured());
    }

    public function shortCircuitsWhenNotConfigured(): void
    {
        $gateway = $this->makeGateway(new MockHandler([]), null, $this->makeSettings(['bookkeeping_frontaccounting_base_url' => '']));

        $result = $gateway->createTransaction($this->invoiceTransaction());

        Assert::false($result->success);
        Assert::same($result->message, 'FrontAccounting is not configured.');
    }

    public function postsAnInvoiceForAnExistingCustomerAndReturnsTheFaNumber(): void
    {
        $mock = new MockHandler([
            $this->json([['debtor_no' => '3', 'debtor_ref' => 'Y3I-7']]),   // GET /customers/
            $this->json([['branch_code' => '4']]),                            // GET /customers/3/branches/
            $this->json('Invoice # 8 has been entered'),                      // POST /sales/
            $this->json(['trans_no' => 8, 'reference' => '003/2026', 'total' => 100.0]), // GET /sales/lookup/
        ]);

        $result = $this->makeGateway($mock)->createTransaction($this->invoiceTransaction());

        Assert::true($result->success);
        Assert::same($result->providerReference, '8');

        $sales = $this->requestAt(2);
        Assert::same($sales->getUri()->getPath(), '/modules/api/sales/');
        $form = $this->formAt(2);
        Assert::same($form->get('trans_type'), '10');
        Assert::same($form->get('comments'), 'INV-9-invoice');
        Assert::same($form->get('cust_ref'), 'INV-9');
        Assert::same($form->get('order_date'), '2026-09-19');
        Assert::same($form->get('delivery_date'), '2026-10-19');
        Assert::same($form->get('customer_id'), '3');
        Assert::same($form->get('branch_id'), '4');
        Assert::same($form->get('items', '0', 'stock_id'), '301');
        Assert::same($form->get('items', '0', 'price'), '100.00');
        Assert::same($this->requestAt(0)->getHeaderLine('X-PASSWORD'), 'secret');
        Assert::same($this->requestAt(0)->getHeaderLine('X-COMPANY'), '0');
    }

    public function createsTheCustomerWhenItDoesNotExistYet(): void
    {
        $mock = new MockHandler([
            $this->json([]),                                                  // GET /customers/ (none)
            $this->json('created'),                                           // POST /customers/
            $this->json([['debtor_no' => '5', 'debtor_ref' => 'Y3I-7']]),    // GET /customers/ again
            $this->json([['branch_code' => '6']]),
            $this->json('ok'),
            $this->json(['trans_no' => 9, 'reference' => '004/2026', 'total' => 100.0]),
        ]);

        $result = $this->makeGateway($mock)->createTransaction($this->invoiceTransaction());

        Assert::true($result->success);
        $customer = $this->formAt(1);
        Assert::same($customer->get('name'), 'Acme Ltd');
        Assert::same($customer->get('debtor_ref'), 'Y3I-7');
        Assert::same($customer->get('curr_code'), 'GBP');
    }

    public function failsWhenTheInvoiceHasNoCustomer(): void
    {
        $gateway = new FrontAccountingGateway(
            $this->makeSettings(),
            $this->makeLogger(),
            $this->makeDocuments(null),
            $this->exportedInvoiceRepository(null),
            new HttpClient(['handler' => HandlerStack::create(new MockHandler([]))]),
        );

        $result = $gateway->createTransaction($this->invoiceTransaction());

        Assert::false($result->success);
        Assert::same($result->message, 'No customer found for the invoice behind INV-9-invoice.');
    }

    public function postsACreditNoteUsingTheDebitSalesAmount(): void
    {
        $credit = new BookkeepingTransaction(
            BookkeepingTransactionType::CreditNote,
            'CN-3-creditnote',
            new DateTimeImmutable('2026-09-20'),
            'GBP',
            [
                new BookkeepingLine(AccountRole::Sales, DebitCredit::Debit, 50.00),
                new BookkeepingLine(AccountRole::AccountsReceivable, DebitCredit::Credit, 50.00),
            ],
            9,
        );
        $mock = new MockHandler([
            $this->json([['debtor_no' => '3', 'debtor_ref' => 'Y3I-7']]),
            $this->json([['branch_code' => '4']]),
            $this->json('ok'),
            $this->json(['trans_no' => 2, 'reference' => '002/2026', 'total' => 50.0]),
        ]);

        $result = $this->makeGateway($mock)->createTransaction($credit);

        Assert::true($result->success);
        Assert::same($result->providerReference, '2');
        $form = $this->formAt(2);
        Assert::same($form->get('trans_type'), '11');
        Assert::same($form->get('items', '0', 'price'), '50.00');
    }

    public function postsAPaymentAllocatedToTheExportedInvoice(): void
    {
        $payment = new BookkeepingTransaction(
            BookkeepingTransactionType::PaymentReceived,
            'INV-9-payment',
            new DateTimeImmutable('2026-09-21'),
            'GBP',
            [
                new BookkeepingLine(AccountRole::Bank, DebitCredit::Debit, 120.00),
                new BookkeepingLine(AccountRole::AccountsReceivable, DebitCredit::Credit, 120.00),
            ],
            9,
        );
        $mock = new MockHandler([
            $this->json([['debtor_no' => '3', 'debtor_ref' => 'Y3I-7']]),
            $this->json([['branch_code' => '4']]),
            $this->json(['id' => 6, 'reference' => '002/2026']),
        ]);

        $result = $this->makeGateway($mock)->createTransaction($payment);

        Assert::true($result->success);
        Assert::same($result->providerReference, '6');
        $form = $this->formAt(2);
        Assert::same($form->get('amount'), '120.00');
        Assert::same($form->get('bank_account'), '1');
        Assert::same($form->get('memo'), 'INV-9-payment');
        Assert::same($form->get('allocate_to', 'type'), '10');
        Assert::same($form->get('allocate_to', 'trans_no'), '8');
    }

    public function aPaymentWaitsForTheInvoiceToBeExported(): void
    {
        $payment = new BookkeepingTransaction(
            BookkeepingTransactionType::PaymentReceived,
            'INV-9-payment',
            new DateTimeImmutable('2026-09-21'),
            'GBP',
            [
                new BookkeepingLine(AccountRole::Bank, DebitCredit::Debit, 120.00),
                new BookkeepingLine(AccountRole::AccountsReceivable, DebitCredit::Credit, 120.00),
            ],
            9,
        );

        $result = $this->makeGateway(new MockHandler([]), null, null, $this->exportedInvoiceRepository(null))
            ->createTransaction($payment);

        Assert::false($result->success);
        Assert::same($result->message, 'The invoice has not been exported to FrontAccounting yet.');
    }

    public function voidsTheExportedInvoiceThroughFrontAccountingsOwnVoid(): void
    {
        $void = new BookkeepingTransaction(
            BookkeepingTransactionType::Void,
            'INV-9-void',
            new DateTimeImmutable('2026-09-22'),
            'GBP',
            [
                new BookkeepingLine(AccountRole::Sales, DebitCredit::Debit, 100.00),
                new BookkeepingLine(AccountRole::AccountsReceivable, DebitCredit::Credit, 100.00),
            ],
            9,
        );
        $mock = new MockHandler([$this->json(['msg' => 'voided', 'id' => '8'])]);

        $result = $this->makeGateway($mock)->createTransaction($void);

        Assert::true($result->success);
        Assert::same($result->providerReference, '8');
        Assert::same($this->lastRequest()->getMethod(), 'DELETE');
        Assert::same($this->lastRequest()->getUri()->getPath(), '/modules/api/journal/10/8');
    }

    public function refundsAreReportedAsNotSupportedYet(): void
    {
        $refund = new BookkeepingTransaction(
            BookkeepingTransactionType::Refund,
            'INV-9-refund',
            new DateTimeImmutable('2026-09-23'),
            'GBP',
            [
                new BookkeepingLine(AccountRole::Sales, DebitCredit::Debit, 100.00),
                new BookkeepingLine(AccountRole::Bank, DebitCredit::Credit, 100.00),
            ],
            9,
        );

        $result = $this->makeGateway(new MockHandler([]))->createTransaction($refund);

        Assert::false($result->success);
        Assert::same($result->message, 'FrontAccounting does not support refund entries yet.');
    }

    public function updatesAreRefusedBecausePostedDocumentsAreImmutable(): void
    {
        $result = $this->makeGateway(new MockHandler([]))->updateTransaction($this->invoiceTransaction());

        Assert::false($result->success);
    }

    public function getTransactionFindsAnInvoiceByItsReference(): void
    {
        $mock = new MockHandler([$this->json(['trans_no' => 8, 'reference' => '003/2026'])]);

        $result = $this->makeGateway($mock)->getTransaction('INV-9-invoice');

        Assert::true($result->found);
        Assert::same($result->providerReference, '8');
        $query = FormReader::fromBody($this->lastRequest()->getUri()->getQuery());
        Assert::same($query->get('trans_type'), '10');
        Assert::same($query->get('comments'), 'INV-9-invoice');
    }

    public function getTransactionTreatsA404AsNotFound(): void
    {
        $mock = new MockHandler([
            new RequestException('Not found', new Psr7Request('GET', 'x'), new Response(404, [], '{"code":404}')),
        ]);

        $result = $this->makeGateway($mock)->getTransaction('INV-9-payment');

        Assert::false($result->found);
        Assert::same($result->message, '');
    }

    public function getTransactionReportsNotFoundForReferencesItCannotLookUp(): void
    {
        $result = $this->makeGateway(new MockHandler([]))->getTransaction('INV-9-void');

        Assert::false($result->found);
        Assert::same($result->message, '');
    }

    public function connectionFailuresBecomeFailedResultsNotExceptions(): void
    {
        $mock = new MockHandler([new ConnectException('Connection refused', new Psr7Request('GET', 'x'))]);

        $result = $this->makeGateway($mock)->createTransaction($this->invoiceTransaction());

        Assert::false($result->success);
        Assert::same($result->message, 'Connection refused');
    }

    public function anApiErrorBodyIsSurfacedAsTheFailureMessage(): void
    {
        $mock = new MockHandler([
            $this->json([['debtor_no' => '3', 'debtor_ref' => 'Y3I-7']]),
            $this->json([['branch_code' => '4']]),
            new RequestException('boom', new Psr7Request('POST', 'x'), new Response(500, [], '<h1>Slim Application Error</h1><p>Column tax_included</p>')),
        ]);

        $result = $this->makeGateway($mock)->createTransaction($this->invoiceTransaction());

        Assert::false($result->success);
        Assert::same($result->message, 'Slim Application Error Column tax_included');
    }

    public function deleteTransactionVoidsAnInvoiceAndRefusesOtherTypes(): void
    {
        $mock = new MockHandler([$this->json(['msg' => 'voided', 'id' => '8'])]);
        $gateway = $this->makeGateway($mock);

        Assert::true($gateway->deleteTransaction('INV-9-invoice')->success);
        Assert::false($gateway->deleteTransaction('INV-9-payment')->success);
    }

    public function sendsVatAsItsOwnLineWhenAVatItemIsConfiguredAndAcceptsTheMatchingTotal(): void
    {
        $mock = new MockHandler([
            $this->json([['debtor_no' => '3', 'debtor_ref' => 'Y3I-7']]),
            $this->json([['branch_code' => '4']]),
            $this->json('ok'),
            $this->json(['trans_no' => 8, 'reference' => '003/2026', 'total' => 120.0]),
        ]);
        $settings = $this->makeSettings(['bookkeeping_frontaccounting_vat_stock_id' => '302']);

        $result = $this->makeGateway($mock, null, $settings)->createTransaction($this->invoiceTransaction());

        Assert::true($result->success);
        $form = $this->formAt(2);
        Assert::same($form->get('items', '0', 'price'), '100.00');
        Assert::same($form->get('items', '1', 'stock_id'), '302');
        Assert::same($form->get('items', '1', 'price'), '20.00');
    }

    public function voidsTheDocumentAndFailsWhenFrontAccountingsTotalDiffersFromTheLedger(): void
    {
        $mock = new MockHandler([
            $this->json([['debtor_no' => '3', 'debtor_ref' => 'Y3I-7']]),
            $this->json([['branch_code' => '4']]),
            $this->json('ok'),
            $this->json(['trans_no' => 8, 'reference' => '003/2026', 'total' => 95.24]),
            $this->json(['msg' => 'voided', 'id' => '8']),
        ]);

        $result = $this->makeGateway($mock)->createTransaction($this->invoiceTransaction());

        Assert::false($result->success);
        Assert::same(
            $result->message,
            'FrontAccounting total 95.24 does not match the ledger total 100.00 (check the tax group and VAT item settings); the document was voided.',
        );
        Assert::same($this->lastRequest()->getMethod(), 'DELETE');
        Assert::same($this->lastRequest()->getUri()->getPath(), '/modules/api/journal/10/8');
    }

    public function createsNewCustomersInTheConfiguredTaxGroup(): void
    {
        $mock = new MockHandler([
            $this->json([]),
            $this->json('created'),
            $this->json([['debtor_no' => '5', 'debtor_ref' => 'Y3I-7']]),
            $this->json([['branch_code' => '6']]),
            $this->json('ok'),
            $this->json(['trans_no' => 9, 'reference' => '004/2026', 'total' => 100.0]),
        ]);
        $settings = $this->makeSettings(['bookkeeping_frontaccounting_tax_group' => '3']);

        $this->makeGateway($mock, null, $settings)->createTransaction($this->invoiceTransaction());

        Assert::same($this->formAt(1)->get('tax_group_id'), '3');
    }
}
