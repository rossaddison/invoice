<?php

declare(strict_types=1);

namespace Tests\Testo\Bookkeeping\Infrastructure\QuickBooks;

use App\Bookkeeping\Domain\AccountRole;
use App\Bookkeeping\Domain\BookkeepingLine;
use App\Bookkeeping\Domain\BookkeepingTransaction;
use App\Bookkeeping\Domain\BookkeepingTransactionType;
use App\Bookkeeping\Domain\DebitCredit;
use App\Bookkeeping\Infrastructure\QuickBooks\QuickBooksGateway;
use App\Infrastructure\Persistence\Setting\Setting;
use App\Invoice\Setting\SettingRepository;
use DateTimeImmutable;
use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Mockery as m;
use Psr\Log\LoggerInterface;
use Testo\Assert;
use Testo\Test;

/**
 * Covers QuickBooksGateway against a mocked Guzzle handler — no real
 * network calls. Endpoints/OAuth2 flow match `bin/quickbooks/
 * QuickBooksOAuthClient.php` (PR #1339, live-verified against the real
 * Intuit sandbox); this test only proves the request/response mapping
 * around them, following PaypalPaymentServiceTest's own MockHandler
 * convention.
 */
#[Test]
final class QuickBooksGatewayTest
{
    /**
     * @return array<string, string>
     */
    private function defaultSettings(): array
    {
        return [
            'bookkeeping_quickbooks_client_id' => 'test-client-id',
            'bookkeeping_quickbooks_client_secret' => 'enc:test-client-secret',
            'bookkeeping_quickbooks_realm_id' => '123145',
            'bookkeeping_quickbooks_refresh_token' => 'enc:test-refresh-token',
            'bookkeeping_quickbooks_access_token' => 'cached-access-token',
            'bookkeeping_quickbooks_access_token_expires_at' => (string) (time() + 3600),
            'bookkeeping_quickbooks_sandbox' => '1',
            'bookkeeping_quickbooks_account_accounts_receivable' => '11',
            'bookkeeping_quickbooks_account_sales' => '12',
            'bookkeeping_quickbooks_account_vat_or_tax' => '13',
            'bookkeeping_quickbooks_account_bank' => '14',
            'bookkeeping_quickbooks_account_payment_fees' => '15',
        ];
    }

    /**
     * @param array<string, string> $overrides
     * @return SettingRepository&m\MockInterface
     */
    private function makeSettingRepository(array $overrides = []): SettingRepository
    {
        $values = array_merge($this->defaultSettings(), $overrides);

        /** @var SettingRepository&m\MockInterface $sR */
        $sR = m::mock(SettingRepository::class);
        $sR->shouldReceive('getSetting')->andReturnUsing(
            static fn(string $key): string => $values[$key] ?? '',
        );
        $sR->shouldReceive('decode')->andReturnUsing(
            static fn(string $data): string => str_starts_with($data, 'enc:') ? substr($data, 4) : $data,
        );
        $sR->shouldReceive('encode')->andReturnUsing(
            static fn(string $data): string => 'enc:' . $data,
        );

        return $sR;
    }

    private function makeHttpClient(MockHandler $mock): HttpClient
    {
        return new HttpClient(['handler' => HandlerStack::create($mock)]);
    }

    private function makeGateway(
        MockHandler $mock,
        ?SettingRepository $settings = null,
    ): QuickBooksGateway {
        /** @var LoggerInterface&m\MockInterface $logger */
        $logger = m::spy(LoggerInterface::class);

        return new QuickBooksGateway(
            $settings ?? $this->makeSettingRepository(),
            $logger,
            $this->makeHttpClient($mock),
        );
    }

    private function paymentReceivedTransaction(
        string $reference = 'INV-501-payment',
        float $amount = 120.00,
    ): BookkeepingTransaction {
        return new BookkeepingTransaction(
            BookkeepingTransactionType::PaymentReceived,
            $reference,
            new DateTimeImmutable('2026-09-18'),
            'GBP',
            [
                new BookkeepingLine(AccountRole::Bank, DebitCredit::Debit, $amount),
                new BookkeepingLine(AccountRole::AccountsReceivable, DebitCredit::Credit, $amount),
            ],
            501,
        );
    }

    private function journalEntryResponse(string $id, string $syncToken = '0'): Response
    {
        return new Response(200, [], json_encode([
            'JournalEntry' => ['Id' => $id, 'SyncToken' => $syncToken],
        ], JSON_THROW_ON_ERROR));
    }

    private function queryResponse(?string $id, ?string $syncToken = '0'): Response
    {
        $journalEntry = $id === null ? [] : [['Id' => $id, 'SyncToken' => $syncToken]];
        return new Response(200, [], json_encode([
            'QueryResponse' => ['JournalEntry' => $journalEntry],
        ], JSON_THROW_ON_ERROR));
    }

    public function getDriverKeyReturnsQuickbooks(): void
    {
        $gateway = $this->makeGateway(new MockHandler([]));

        Assert::same('quickbooks', $gateway->getDriverKey());
    }

    public function isConfiguredReturnsTrueWhenAllCredentialsArePresent(): void
    {
        $gateway = $this->makeGateway(new MockHandler([]));

        Assert::true($gateway->isConfigured());
    }

    public function isConfiguredReturnsFalseWhenRefreshTokenIsMissing(): void
    {
        $settings = $this->makeSettingRepository(['bookkeeping_quickbooks_refresh_token' => '']);
        $gateway = $this->makeGateway(new MockHandler([]), $settings);

        Assert::false($gateway->isConfigured());
    }

    public function everyOperationShortCircuitsWhenQuickBooksIsNotConfigured(): void
    {
        $notConfigured = 'QuickBooks is not configured.';
        $settings = $this->makeSettingRepository(['bookkeeping_quickbooks_client_id' => '']);
        $gateway = $this->makeGateway(new MockHandler([]), $settings);
        $transaction = $this->paymentReceivedTransaction();

        $create = $gateway->createTransaction($transaction);
        Assert::false($create->success);
        Assert::same($notConfigured, $create->message);

        $update = $gateway->updateTransaction($transaction);
        Assert::false($update->success);
        Assert::same($notConfigured, $update->message);

        $lookup = $gateway->getTransaction($transaction->getReference());
        Assert::false($lookup->found);
        Assert::same($notConfigured, $lookup->message);

        $delete = $gateway->deleteTransaction($transaction->getReference());
        Assert::false($delete->success);
        Assert::same($notConfigured, $delete->message);
    }

    public function createTransactionPostsAJournalEntryWithTheCachedAccessTokenAndReturnsTheProviderReference(): void
    {
        $mock = new MockHandler([$this->journalEntryResponse('QB-1')]);
        $gateway = $this->makeGateway($mock);

        $result = $gateway->createTransaction($this->paymentReceivedTransaction());

        Assert::true($result->success);
        Assert::same('QB-1', $result->providerReference);

        $sentRequest = $mock->getLastRequest();
        Assert::notNull($sentRequest);
        Assert::same('Bearer cached-access-token', $sentRequest->getHeaderLine('Authorization'));

        /** @var array{DocNumber: string, TxnDate: string, CurrencyRef: array{value: string}, Line: list<array{Amount: int|float, JournalEntryLineDetail: array{PostingType: string, AccountRef: array{value: string}}}>} $body */
        $body = json_decode((string) $sentRequest->getBody(), true, 512, JSON_THROW_ON_ERROR);
        Assert::same('INV-501-payment', $body['DocNumber']);
        Assert::same('2026-09-18', $body['TxnDate']);
        Assert::same('GBP', $body['CurrencyRef']['value']);
        Assert::count($body['Line'], 2);
        Assert::same('Debit', $body['Line'][0]['JournalEntryLineDetail']['PostingType']);
        Assert::same('14', $body['Line'][0]['JournalEntryLineDetail']['AccountRef']['value']);
        Assert::same(120.00, (float) $body['Line'][0]['Amount']);
        Assert::same('Credit', $body['Line'][1]['JournalEntryLineDetail']['PostingType']);
        Assert::same('11', $body['Line'][1]['JournalEntryLineDetail']['AccountRef']['value']);
    }

    public function createTransactionRefreshesAnExpiredAccessTokenAndPersistsTheRotatedPair(): void
    {
        $settings = $this->makeSettingRepository([
            'bookkeeping_quickbooks_access_token_expires_at' => (string) (time() - 10),
        ]);
        /** @var Setting&m\MockInterface $storedSetting */
        $storedSetting = m::mock(Setting::class);
        $storedSetting->shouldReceive('setSettingValue')->times(3);
        $settings->shouldReceive('withKey')->times(3)->andReturn($storedSetting);
        $settings->shouldReceive('save')->times(3)->with($storedSetting);

        $mock = new MockHandler([
            new Response(200, [], json_encode([
                'access_token' => 'new-access-token',
                'refresh_token' => 'new-refresh-token',
                'expires_in' => 3600,
            ], JSON_THROW_ON_ERROR)),
            $this->journalEntryResponse('QB-2'),
        ]);
        $gateway = $this->makeGateway($mock, $settings);

        $result = $gateway->createTransaction($this->paymentReceivedTransaction('INV-502-payment'));

        Assert::true($result->success);
        Assert::same('QB-2', $result->providerReference);

        $sentRequest = $mock->getLastRequest();
        Assert::notNull($sentRequest);
        Assert::same('Bearer new-access-token', $sentRequest->getHeaderLine('Authorization'));
    }

    public function createTransactionFailsWhenAnAccountRoleHasNoConfiguredAccount(): void
    {
        $settings = $this->makeSettingRepository(['bookkeeping_quickbooks_account_bank' => '']);
        $gateway = $this->makeGateway(new MockHandler([]), $settings);

        $result = $gateway->createTransaction($this->paymentReceivedTransaction());

        Assert::false($result->success);
        Assert::same('No QuickBooks account configured for role bank.', $result->message);
    }

    public function createTransactionReportsUnableToObtainAnAccessTokenWhenRefreshResponseIsMalformed(): void
    {
        $settings = $this->makeSettingRepository([
            'bookkeeping_quickbooks_access_token_expires_at' => (string) (time() - 10),
        ]);
        $mock = new MockHandler([
            new Response(200, [], json_encode(['token_type' => 'bearer'], JSON_THROW_ON_ERROR)),
        ]);
        $gateway = $this->makeGateway($mock, $settings);

        $result = $gateway->createTransaction($this->paymentReceivedTransaction());

        Assert::false($result->success);
        Assert::same('Unable to obtain a QuickBooks access token.', $result->message);
    }

    public function getTransactionReturnsFoundWhenQueryMatchesAJournalEntry(): void
    {
        $mock = new MockHandler([$this->queryResponse('QB-3', '2')]);
        $gateway = $this->makeGateway($mock);

        $result = $gateway->getTransaction('INV-503-payment');

        Assert::true($result->found);
        Assert::same('QB-3', $result->providerReference);
    }

    public function getTransactionReturnsNotFoundWhenQueryResponseIsEmpty(): void
    {
        $mock = new MockHandler([$this->queryResponse(null)]);
        $gateway = $this->makeGateway($mock);

        $result = $gateway->getTransaction('INV-504-payment');

        Assert::false($result->found);
        Assert::same('', $result->message);
    }

    public function updateTransactionUpdatesTheExistingJournalEntryUsingItsSyncToken(): void
    {
        $mock = new MockHandler([
            $this->queryResponse('QB-5', '3'),
            $this->journalEntryResponse('QB-5', '4'),
        ]);
        $gateway = $this->makeGateway($mock);

        $result = $gateway->updateTransaction($this->paymentReceivedTransaction('INV-505-payment'));

        Assert::true($result->success);
        Assert::same('QB-5', $result->providerReference);

        $sentRequest = $mock->getLastRequest();
        Assert::notNull($sentRequest);
        /** @var array{Id: string, SyncToken: string} $body */
        $body = json_decode((string) $sentRequest->getBody(), true, 512, JSON_THROW_ON_ERROR);
        Assert::same('QB-5', $body['Id']);
        Assert::same('3', $body['SyncToken']);
    }

    public function updateTransactionFailsWhenNoMatchingJournalEntryExists(): void
    {
        $mock = new MockHandler([$this->queryResponse(null)]);
        $gateway = $this->makeGateway($mock);

        $result = $gateway->updateTransaction($this->paymentReceivedTransaction('INV-506-payment'));

        Assert::false($result->success);
        Assert::same(
            'Cannot update: no QuickBooks JournalEntry found for reference INV-506-payment.',
            $result->message,
        );
    }

    public function deleteTransactionDeletesUsingTheLookedUpIdAndSyncToken(): void
    {
        $mock = new MockHandler([
            $this->queryResponse('QB-7', '5'),
            $this->journalEntryResponse('QB-7', '6'),
        ]);
        $gateway = $this->makeGateway($mock);

        $result = $gateway->deleteTransaction('INV-507-payment');

        Assert::true($result->success);
        Assert::same('QB-7', $result->providerReference);

        $sentRequest = $mock->getLastRequest();
        Assert::notNull($sentRequest);
        /** @var array{Id: string, SyncToken: string} $body */
        $body = json_decode((string) $sentRequest->getBody(), true, 512, JSON_THROW_ON_ERROR);
        Assert::same('QB-7', $body['Id']);
        Assert::same('5', $body['SyncToken']);
    }

    public function deleteTransactionFailsWhenNoMatchingJournalEntryExists(): void
    {
        $mock = new MockHandler([$this->queryResponse(null)]);
        $gateway = $this->makeGateway($mock);

        $result = $gateway->deleteTransaction('INV-508-payment');

        Assert::false($result->success);
        Assert::same('No QuickBooks JournalEntry found for reference INV-508-payment.', $result->message);
    }
}
