<?php

declare(strict_types=1);

namespace App\Bookkeeping\Infrastructure\FrontAccounting;

use App\Bookkeeping\Application\BookkeepingDocument;
use App\Bookkeeping\Application\BookkeepingDocumentSourceInterface;
use App\Bookkeeping\Application\BookkeepingGatewayInterface;
use App\Bookkeeping\Application\BookkeepingResult;
use App\Bookkeeping\Application\BookkeepingTransactionLookupResult;
use App\Bookkeeping\Application\BookkeepingTransactionRepositoryInterface;
use App\Bookkeeping\Domain\AccountRole;
use App\Bookkeeping\Domain\BookkeepingTransaction;
use App\Bookkeeping\Domain\BookkeepingTransactionType;
use App\Bookkeeping\Domain\DebitCredit;
use App\Invoice\Setting\SettingRepository;
use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;
use Psr\Log\LoggerInterface;

/**
 * FrontAccounting adapter for BookkeepingGatewayInterface, talking to the
 * Simple API module (rossaddison/cambell-prince FrontAccountingSimpleAPI).
 *
 * Unlike QuickBooks (journal lines), FrontAccounting is document-driven:
 * receivables must go through customer documents or the customer ledger and
 * the GL disagree. So entries map by type:
 *
 *   InvoiceIssued   -> sales invoice   (POST /sales, trans_type 10)
 *   CreditNote      -> credit note     (POST /sales, trans_type 11)
 *   PaymentReceived -> customer payment allocated to the invoice (POST /payments)
 *   Void            -> FrontAccounting's own void (DELETE /journal/10/{no})
 *
 * Refund and MerchantFee entries are not supported yet and report failure.
 *
 * Our reference is stored in the document's memo (`comments`) and is the
 * idempotency key: getTransaction() looks it up via GET /sales/lookup/.
 * FrontAccounting computes tax itself from the customer's tax group, so the
 * gateway sends net Sales as one line and, when a VAT stock item is
 * configured, our VAT as a second line for that item (an item that posts to
 * the VAT liability account, with FrontAccounting's tax group for these
 * customers set to zero-rated). After posting, FrontAccounting's gross total
 * is compared with ours; a mismatch voids the document and reports failure
 * rather than leaving figures that disagree with our ledger.
 */
final class FrontAccountingGateway implements BookkeepingGatewayInterface
{
    private const string KEY_BASE_URL = 'bookkeeping_frontaccounting_base_url';
    private const string KEY_COMPANY = 'bookkeeping_frontaccounting_company';
    private const string KEY_USERNAME = 'bookkeeping_frontaccounting_username';
    private const string KEY_PASSWORD = 'bookkeeping_frontaccounting_password';
    private const string KEY_BANK_ACCOUNT = 'bookkeeping_frontaccounting_bank_account';
    private const string KEY_STOCK_ID = 'bookkeeping_frontaccounting_stock_id';
    private const string KEY_SALES_TYPE = 'bookkeeping_frontaccounting_sales_type';
    private const string KEY_PAYMENT_TERMS = 'bookkeeping_frontaccounting_payment_terms';
    private const string KEY_LOCATION = 'bookkeeping_frontaccounting_location';
    private const string KEY_VAT_STOCK_ID = 'bookkeeping_frontaccounting_vat_stock_id';
    private const string KEY_TAX_GROUP = 'bookkeeping_frontaccounting_tax_group';

    private const int TYPE_INVOICE = 10;
    private const int TYPE_CREDIT_NOTE = 11;
    private const int TYPE_PAYMENT = 12;

    private const string SUFFIX_INVOICE = '-invoice';
    private const string SUFFIX_PAYMENT = '-payment';
    private const string SUFFIX_CREDIT_NOTE = '-creditnote';
    private const string SUFFIX_VOID = '-void';

    private const string MESSAGE_NOT_CONFIGURED = 'FrontAccounting is not configured.';

    public function __construct(
        private readonly SettingRepository $settings,
        private readonly LoggerInterface $logger,
        private readonly BookkeepingDocumentSourceInterface $documents,
        private readonly BookkeepingTransactionRepositoryInterface $transactions,
        private readonly HttpClient $httpClient = new HttpClient(),
    ) {
    }

    #[\Override]
    public function getDriverKey(): string
    {
        return 'frontaccounting';
    }

    #[\Override]
    public function isConfigured(): bool
    {
        foreach ([self::KEY_BASE_URL, self::KEY_COMPANY, self::KEY_USERNAME, self::KEY_PASSWORD, self::KEY_BANK_ACCOUNT, self::KEY_STOCK_ID] as $key) {
            if ($this->settings->getSetting($key) === '') {
                return false;
            }
        }
        return true;
    }

    #[\Override]
    public function createTransaction(BookkeepingTransaction $transaction): BookkeepingResult
    {
        if (!$this->isConfigured()) {
            return new BookkeepingResult(false, message: self::MESSAGE_NOT_CONFIGURED);
        }

        try {
            return match ($transaction->getType()) {
                BookkeepingTransactionType::InvoiceIssued => $this->postSalesDocument($transaction, self::TYPE_INVOICE),
                BookkeepingTransactionType::CreditNote => $this->postSalesDocument($transaction, self::TYPE_CREDIT_NOTE),
                BookkeepingTransactionType::PaymentReceived => $this->postPayment($transaction),
                BookkeepingTransactionType::Void => $this->voidInvoice($this->baseReference($transaction->getReference())),
                default => new BookkeepingResult(
                    false,
                    message: 'FrontAccounting does not support ' . $transaction->getType()->value . ' entries yet.',
                ),
            };
        } catch (GuzzleException|\JsonException|\RuntimeException $e) {
            $this->logger->error('FrontAccounting createTransaction failed.', $this->errorContext($e, $transaction->getReference()));
            return new BookkeepingResult(false, message: $this->errorMessage($e));
        }
    }

    #[\Override]
    public function updateTransaction(BookkeepingTransaction $transaction): BookkeepingResult
    {
        return new BookkeepingResult(
            false,
            message: 'FrontAccounting documents cannot be edited once posted; void and re-issue instead.',
        );
    }

    #[\Override]
    public function getTransaction(string $reference): BookkeepingTransactionLookupResult
    {
        if (!$this->isConfigured()) {
            return BookkeepingTransactionLookupResult::failed(self::MESSAGE_NOT_CONFIGURED);
        }

        $type = $this->documentTypeFor($reference);
        if ($type === null) {
            return BookkeepingTransactionLookupResult::notFound();
        }

        try {
            $found = $this->lookup($type, $reference);
            return $found === null
                ? BookkeepingTransactionLookupResult::notFound()
                : BookkeepingTransactionLookupResult::found((string) $found['trans_no']);
        } catch (GuzzleException|\JsonException|\RuntimeException $e) {
            $this->logger->warning('FrontAccounting getTransaction failed.', $this->errorContext($e, $reference));
            return BookkeepingTransactionLookupResult::failed($this->errorMessage($e));
        }
    }

    #[\Override]
    public function deleteTransaction(string $reference): BookkeepingResult
    {
        if (!$this->isConfigured()) {
            return new BookkeepingResult(false, message: self::MESSAGE_NOT_CONFIGURED);
        }
        if (!str_ends_with($reference, self::SUFFIX_INVOICE)) {
            return new BookkeepingResult(false, message: 'Only invoice entries can be voided in FrontAccounting.');
        }

        try {
            return $this->voidInvoice($this->baseReference($reference));
        } catch (GuzzleException|\JsonException|\RuntimeException $e) {
            $this->logger->error('FrontAccounting deleteTransaction failed.', $this->errorContext($e, $reference));
            return new BookkeepingResult(false, message: $this->errorMessage($e));
        }
    }

    private function postSalesDocument(BookkeepingTransaction $transaction, int $type): BookkeepingResult
    {
        $document = $this->documentFor($transaction);
        if ($document === null) {
            return new BookkeepingResult(false, message: 'No customer found for the invoice behind ' . $transaction->getReference() . '.');
        }
        $net = $this->lineTotal(
            $transaction,
            AccountRole::Sales,
            $type === self::TYPE_INVOICE ? DebitCredit::Credit : DebitCredit::Debit,
        );
        if ($net <= 0.00) {
            return new BookkeepingResult(false, message: 'The entry has no Sales amount to post to FrontAccounting.');
        }

        $vat = $this->lineTotal(
            $transaction,
            AccountRole::VatOrTax,
            $type === self::TYPE_INVOICE ? DebitCredit::Credit : DebitCredit::Debit,
        );
        $vatStockId = $this->settings->getSetting(self::KEY_VAT_STOCK_ID);
        $items = [[
            'stock_id' => $this->settings->getSetting(self::KEY_STOCK_ID),
            'qty' => '1',
            'price' => number_format($net, 2, '.', ''),
            'discount' => '0',
            'description' => $document->documentNumber,
        ]];
        if ($vat > 0.00 && $vatStockId !== '') {
            $items[] = [
                'stock_id' => $vatStockId,
                'qty' => '1',
                'price' => number_format($vat, 2, '.', ''),
                'discount' => '0',
                'description' => 'VAT ' . $document->documentNumber,
            ];
        }

        $customer = $this->ensureCustomer($document, $transaction->getCurrency());
        $due = $type === self::TYPE_INVOICE ? $document->dueDate : $transaction->getDate();

        $this->request('POST', '/sales/', [
            'form_params' => [
                'trans_type' => $type,
                'comments' => $transaction->getReference(),
                'order_date' => $transaction->getDate()->format('Y-m-d'),
                'delivery_date' => $due->format('Y-m-d'),
                'payment' => $this->settings->getSetting(self::KEY_PAYMENT_TERMS) ?: '3',
                'cust_ref' => $document->documentNumber,
                'deliver_to' => $document->customerName,
                'delivery_address' => $document->customerAddress,
                'phone' => '0',
                'ship_via' => '1',
                'location' => $this->settings->getSetting(self::KEY_LOCATION) ?: 'DEF',
                'customer_id' => $customer['debtor_no'],
                'branch_id' => $customer['branch_code'],
                'sales_type' => $this->settings->getSetting(self::KEY_SALES_TYPE) ?: '1',
                'dimension_id' => '0',
                'dimension2_id' => '0',
                'freight_cost' => '0',
                'items' => $items,
            ],
        ]);

        $created = $this->lookup($type, $transaction->getReference());
        if ($created === null) {
            return new BookkeepingResult(false, message: 'FrontAccounting accepted the document but it could not be found afterwards.');
        }

        $expected = round($net + ($vatStockId !== '' ? $vat : 0.00), 2);
        if (abs($created['total'] - $expected) > 0.01) {
            $this->request('DELETE', '/journal/' . $type . '/' . $created['trans_no']);
            return new BookkeepingResult(false, message: sprintf(
                'FrontAccounting total %.2f does not match the ledger total %.2f (check the tax group and VAT item settings); the document was voided.',
                $created['total'],
                $expected,
            ));
        }

        return new BookkeepingResult(true, (string) $created['trans_no']);
    }

    private function postPayment(BookkeepingTransaction $transaction): BookkeepingResult
    {
        $document = $this->documentFor($transaction);
        if ($document === null) {
            return new BookkeepingResult(false, message: 'No customer found for the invoice behind ' . $transaction->getReference() . '.');
        }
        $invoiceNo = $this->exportedInvoiceNumber($this->baseReference($transaction->getReference()));
        if ($invoiceNo === null) {
            return new BookkeepingResult(false, message: 'The invoice has not been exported to FrontAccounting yet.');
        }
        $amount = $this->lineTotal($transaction, AccountRole::Bank, DebitCredit::Debit);
        if ($amount <= 0.00) {
            return new BookkeepingResult(false, message: 'The entry has no Bank amount to post to FrontAccounting.');
        }

        $customer = $this->ensureCustomer($document, $transaction->getCurrency());
        /** @var array{id?: int|string} $response */
        $response = $this->request('POST', '/payments/', [
            'form_params' => [
                'customer_id' => $customer['debtor_no'],
                'branch_id' => $customer['branch_code'],
                'bank_account' => $this->settings->getSetting(self::KEY_BANK_ACCOUNT),
                'amount' => number_format($amount, 2, '.', ''),
                'trans_date' => $transaction->getDate()->format('Y-m-d'),
                'memo' => $transaction->getReference(),
                'allocate_to' => ['type' => self::TYPE_INVOICE, 'trans_no' => $invoiceNo],
            ],
        ]);

        return isset($response['id']) && $response['id'] !== ''
            ? new BookkeepingResult(true, (string) $response['id'])
            : new BookkeepingResult(false, message: 'FrontAccounting response missing the payment id.');
    }

    private function voidInvoice(string $baseReference): BookkeepingResult
    {
        $invoiceNo = $this->exportedInvoiceNumber($baseReference);
        if ($invoiceNo === null) {
            return new BookkeepingResult(false, message: 'The invoice has not been exported to FrontAccounting, so there is nothing to void.');
        }
        $this->request('DELETE', '/journal/' . self::TYPE_INVOICE . '/' . $invoiceNo);

        return new BookkeepingResult(true, $invoiceNo);
    }

    /**
     * @return array{debtor_no: string, branch_code: string}
     */
    private function ensureCustomer(BookkeepingDocument $document, string $currency): array
    {
        $debtorNo = $this->findDebtorNo($document->customerReference);
        if ($debtorNo === null) {
            $this->request('POST', '/customers/', [
                'form_params' => [
                    'name' => $document->customerName,
                    'debtor_ref' => $document->customerReference,
                    'address' => $document->customerAddress,
                    'tax_id' => 'N/A',
                    'curr_code' => $currency,
                    'credit_status' => '1',
                    'payment_terms' => $this->settings->getSetting(self::KEY_PAYMENT_TERMS) ?: '3',
                    'discount' => '0',
                    'pymt_discount' => '0',
                    'credit_limit' => '0',
                    'sales_type' => $this->settings->getSetting(self::KEY_SALES_TYPE) ?: '1',
                    'salesman' => '1',
                    'area' => '1',
                    'tax_group_id' => $this->settings->getSetting(self::KEY_TAX_GROUP) ?: '1',
                ],
            ]);
            $debtorNo = $this->findDebtorNo($document->customerReference);
        }
        if ($debtorNo === null) {
            throw new \RuntimeException('FrontAccounting customer ' . $document->customerReference . ' could not be found or created.');
        }

        /** @var list<array{branch_code?: string}> $branches */
        $branches = $this->request('GET', '/customers/' . $debtorNo . '/branches/');
        $branchCode = $branches[0]['branch_code'] ?? null;
        if ($branchCode === null) {
            throw new \RuntimeException('FrontAccounting customer ' . $debtorNo . ' has no branch.');
        }

        return ['debtor_no' => $debtorNo, 'branch_code' => $branchCode];
    }

    private function findDebtorNo(string $customerReference): ?string
    {
        /** @var list<array{debtor_no?: string, debtor_ref?: string}> $customers */
        $customers = $this->request('GET', '/customers/', ['query' => ['debtor_ref' => $customerReference]]);
        foreach ($customers as $customer) {
            if (($customer['debtor_ref'] ?? null) === $customerReference && isset($customer['debtor_no'])) {
                return $customer['debtor_no'];
            }
        }
        return null;
    }

    /**
     * @return array{trans_no: int, total: float}|null
     */
    private function lookup(int $type, string $reference): ?array
    {
        try {
            /** @var array{trans_no?: int|string, total?: float|int|string} $data */
            $data = $this->request('GET', '/sales/lookup/', ['query' => ['trans_type' => $type, 'comments' => $reference]]);
        } catch (RequestException $e) {
            if ($e->getResponse()?->getStatusCode() === 404) {
                return null;
            }
            throw $e;
        }
        return isset($data['trans_no'])
            ? ['trans_no' => (int) $data['trans_no'], 'total' => (float) ($data['total'] ?? 0)]
            : null;
    }

    private function exportedInvoiceNumber(string $baseReference): ?string
    {
        return $this->transactions
            ->findByReference($baseReference . self::SUFFIX_INVOICE)
            ?->getExportedProviderReference();
    }

    private function documentFor(BookkeepingTransaction $transaction): ?BookkeepingDocument
    {
        $invId = $transaction->getSourceInvId();
        return $invId === null ? null : $this->documents->forInvoice($invId);
    }

    private function lineTotal(BookkeepingTransaction $transaction, AccountRole $role, DebitCredit $direction): float
    {
        $total = 0.00;
        foreach ($transaction->getLines() as $line) {
            if ($line->account === $role && $line->direction === $direction) {
                $total += $line->amount;
            }
        }
        return round($total, 2);
    }

    private function baseReference(string $reference): string
    {
        foreach ([self::SUFFIX_INVOICE, self::SUFFIX_PAYMENT, self::SUFFIX_CREDIT_NOTE, self::SUFFIX_VOID] as $suffix) {
            if (str_ends_with($reference, $suffix)) {
                return substr($reference, 0, -strlen($suffix));
            }
        }
        return $reference;
    }

    private function documentTypeFor(string $reference): ?int
    {
        return match (true) {
            str_ends_with($reference, self::SUFFIX_INVOICE) => self::TYPE_INVOICE,
            str_ends_with($reference, self::SUFFIX_CREDIT_NOTE) => self::TYPE_CREDIT_NOTE,
            str_ends_with($reference, self::SUFFIX_PAYMENT) => self::TYPE_PAYMENT,
            default => null,
        };
    }

    /**
     * @param array<string, mixed> $options
     * @return array<array-key, mixed>
     * @throws GuzzleException
     * @throws \JsonException
     */
    private function request(string $method, string $path, array $options = []): array
    {
        $password = $this->settings->getSetting(self::KEY_PASSWORD);
        $options['headers'] = [
            'X-COMPANY' => $this->settings->getSetting(self::KEY_COMPANY),
            'X-USER' => $this->settings->getSetting(self::KEY_USERNAME),
            'X-PASSWORD' => (string) $this->settings->decode($password),
            'Accept' => 'application/json',
        ];

        $response = $this->httpClient->request(
            $method,
            rtrim($this->settings->getSetting(self::KEY_BASE_URL), '/') . $path,
            $options,
        );
        $body = (string) $response->getBody();
        if ($body === '') {
            return [];
        }
        try {
            /** @var array<array-key, mixed>|string $data */
            $data = json_decode($body, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            // POST /sales answers with a plain-text message; callers that
            // write re-query afterwards, so only reads need valid JSON.
            if ($method === 'GET') {
                throw $e;
            }
            return [];
        }

        return is_array($data) ? $data : [];
    }

    private function errorMessage(\Throwable $e): string
    {
        $response = $e instanceof RequestException ? $e->getResponse() : null;
        if ($response !== null) {
            $html = preg_replace('#<style.*?</style>#is', '', (string) $response->getBody()) ?? '';
            $body = preg_replace('/<[^>]*>/', ' ', $html) ?? '';
            $body = trim(preg_replace('/\s+/', ' ', $body) ?? '');
            if ($body !== '') {
                return mb_substr($body, 0, 300);
            }
        }
        return $e->getMessage();
    }

    /**
     * @return array<string, string>
     */
    private function errorContext(\Throwable $e, string $reference): array
    {
        return ['reference' => $reference, 'error' => $this->errorMessage($e)];
    }
}
