<?php

declare(strict_types=1);

namespace App\Bookkeeping\Infrastructure\QuickBooks;

use App\Bookkeeping\Application\BookkeepingGatewayInterface;
use App\Bookkeeping\Application\BookkeepingResult;
use App\Bookkeeping\Application\BookkeepingTransactionLookupResult;
use App\Bookkeeping\Domain\AccountRole;
use App\Bookkeeping\Domain\BookkeepingLine;
use App\Bookkeeping\Domain\BookkeepingTransaction;
use App\Infrastructure\Persistence\Setting\Setting;
use App\Invoice\Setting\SettingRepository;
use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;
use Psr\Log\LoggerInterface;

/**
 * QuickBooks Online adapter for BookkeepingGatewayInterface — this app's
 * chosen fallback bookkeeping provider (see
 * project_bookkeeping_ddd_module_design's "QuickBooks as fallback, our own
 * ledger is the real system" direction).
 *
 * Maps a BookkeepingTransaction onto QBO's JournalEntry resource
 * (`POST /v3/company/{realmId}/journalentry`), one JournalEntryLineDetail
 * per BookkeepingLine, `AccountRef.value` resolved from this app's own
 * Settings (`bookkeeping_quickbooks_account_{role}` — mirrors this
 * module's own documented "each provider owns its own small role-to-
 * account-code mapping" design decision, same shape as this app's
 * existing `gateway_{driver}_*` payment-gateway credential settings).
 *
 * OAuth2 endpoints/flow match `bin/quickbooks/QuickBooksOAuthClient.php`
 * (PR #1339, live-verified against the real Intuit sandbox) exactly, but
 * persisted through SettingRepository rather than a local JSON file —
 * refresh tokens rotate on every use, so every refresh call here
 * overwrites the stored access/refresh token pair, matching how that
 * scaffold's own `saveTokens()` always keeps only the latest pair valid.
 *
 * The one-time authorization_code exchange (the interactive consent step
 * a human must click through) is deliberately NOT part of this class —
 * it's a one-time setup action, not a runtime gateway operation. Until a
 * "Connect to QuickBooks" settings-UI flow exists, `bookkeeping_quickbooks_
 * refresh_token`/`_realm_id` must be seeded manually (e.g. via the same
 * `bin/quickbooks/2-get-tokens.php` flow, copied into Settings) — this
 * class only ever refreshes and uses a refresh_token, never obtains the
 * first one.
 *
 * Production base URL (`quickbooks.api.intuit.com`) is taken from Intuit's
 * general API reference by convention (sandbox prefix removed) — NOT
 * independently re-verified live this session, unlike every sandbox URL
 * here, which was proven live in PR #1339. Confirm before ever flipping
 * `bookkeeping_quickbooks_sandbox` off in production.
 */
final class QuickBooksGateway implements BookkeepingGatewayInterface
{
    private const string ACCOUNT_KEY_PREFIX = 'bookkeeping_quickbooks_account_';

    private const string PATH_JOURNAL_ENTRY = '/journalentry';
    private const string MESSAGE_NOT_CONFIGURED = 'QuickBooks is not configured.';
    private const string MESSAGE_NO_ACCESS_TOKEN = 'Unable to obtain a QuickBooks access token.';

    private readonly QuickBooksConnection $connection;

    public function __construct(
        private readonly SettingRepository $settings,
        private readonly LoggerInterface $logger,
        private readonly HttpClient $httpClient = new HttpClient(),
    ) {
        $this->connection = new QuickBooksConnection($settings, $logger, $httpClient);
    }

    #[\Override]
    public function getDriverKey(): string
    {
        return 'quickbooks';
    }

    #[\Override]
    public function isConfigured(): bool
    {
        return $this->connection->isConfigured();
    }

    #[\Override]
    public function createTransaction(BookkeepingTransaction $transaction): BookkeepingResult
    {
        if (!$this->isConfigured()) {
            return new BookkeepingResult(false, message: self::MESSAGE_NOT_CONFIGURED);
        }

        $lines = $this->buildLines($transaction->getLines());
        if (is_string($lines)) {
            return new BookkeepingResult(false, message: $lines);
        }

        return $this->resolveTokenAndCreate($transaction, $lines);
    }

    /**
     * Split out of createTransaction() purely to keep its own return count
     * within SonarCloud's php:S1142 limit (3) — the configured/lines guard
     * clauses above already cover two of createTransaction()'s own
     * precondition failures, so this only needs to cover the remaining
     * token precondition plus delegating to the POST itself.
     *
     * @param list<array<string, mixed>> $lines
     */
    private function resolveTokenAndCreate(BookkeepingTransaction $transaction, array $lines): BookkeepingResult
    {
        $token = $this->connection->accessToken();
        if ($token === null) {
            return new BookkeepingResult(false, message: self::MESSAGE_NO_ACCESS_TOKEN);
        }

        return $this->postNewJournalEntry($transaction, $lines, $token);
    }

    /**
     * Split out of resolveTokenAndCreate() purely to keep its own return
     * count within SonarCloud's php:S1142 limit (3) — this only needs to
     * cover the POST's own outcomes.
     *
     * @param list<array<string, mixed>> $lines
     */
    private function postNewJournalEntry(BookkeepingTransaction $transaction, array $lines, string $token): BookkeepingResult
    {
        try {
            $response = $this->httpClient->post($this->connection->companyUrl() . self::PATH_JOURNAL_ENTRY, [
                'headers' => $this->connection->authHeaders($token),
                'json' => $this->journalEntryPayload($transaction, $lines),
            ]);
            /** @var array{JournalEntry?: array{Id?: string}} $data */
            $data = json_decode((string) $response->getBody(), true, 512, JSON_THROW_ON_ERROR);
            $id = $data['JournalEntry']['Id'] ?? null;
            if ($id === null) {
                $this->logger->error('QuickBooks createTransaction: response missing JournalEntry.Id.', [
                    'reference' => $transaction->getReference(),
                ]);
                return new BookkeepingResult(false, message: 'QuickBooks response missing JournalEntry id.');
            }
            return new BookkeepingResult(true, $id);
        } catch (GuzzleException|\JsonException $e) {
            $this->logger->error('QuickBooks createTransaction failed.', $this->errorLogContext($e));
            return new BookkeepingResult(false, message: $e->getMessage());
        }
    }

    #[\Override]
    public function updateTransaction(BookkeepingTransaction $transaction): BookkeepingResult
    {
        if (!$this->isConfigured()) {
            return new BookkeepingResult(false, message: self::MESSAGE_NOT_CONFIGURED);
        }

        $lines = $this->buildLines($transaction->getLines());
        if (is_string($lines)) {
            return new BookkeepingResult(false, message: $lines);
        }

        return $this->resolveTokenAndUpdate($transaction, $lines);
    }

    /**
     * Split out of updateTransaction() purely to keep its own return count
     * within SonarCloud's php:S1142 limit (3) — the configured/lines guard
     * clauses above already cover two of updateTransaction()'s own
     * precondition failures, so this only needs to cover the remaining
     * token precondition plus delegating to the lookup-then-POST itself.
     *
     * @param list<array<string, mixed>> $lines
     */
    private function resolveTokenAndUpdate(BookkeepingTransaction $transaction, array $lines): BookkeepingResult
    {
        $token = $this->connection->accessToken();
        if ($token === null) {
            return new BookkeepingResult(false, message: self::MESSAGE_NO_ACCESS_TOKEN);
        }

        return $this->postUpdatedJournalEntry($transaction, $lines, $token);
    }

    /**
     * Split out of resolveTokenAndUpdate() purely to keep its own return
     * count within SonarCloud's php:S1142 limit (3) — this only needs to
     * cover the lookup-then-POST's own outcomes.
     *
     * @param list<array<string, mixed>> $lines
     */
    private function postUpdatedJournalEntry(BookkeepingTransaction $transaction, array $lines, string $token): BookkeepingResult
    {
        try {
            $entry = $this->findJournalEntry($transaction->getReference(), $token);
            if ($entry === null) {
                return new BookkeepingResult(
                    false,
                    message: 'Cannot update: no QuickBooks JournalEntry found for reference ' . $transaction->getReference() . '.',
                );
            }

            $payload = $this->journalEntryPayload($transaction, $lines);
            $payload['Id'] = $entry['Id'];
            $payload['SyncToken'] = $entry['SyncToken'];

            $response = $this->httpClient->post($this->connection->companyUrl() . self::PATH_JOURNAL_ENTRY, [
                'headers' => $this->connection->authHeaders($token),
                'json' => $payload,
            ]);
            /** @var array{JournalEntry?: array{Id?: string}} $data */
            $data = json_decode((string) $response->getBody(), true, 512, JSON_THROW_ON_ERROR);
            return new BookkeepingResult(true, $data['JournalEntry']['Id'] ?? $entry['Id']);
        } catch (GuzzleException|\JsonException $e) {
            $this->logger->error('QuickBooks updateTransaction failed.', $this->errorLogContext($e));
            return new BookkeepingResult(false, message: $e->getMessage());
        }
    }

    #[\Override]
    public function getTransaction(string $reference): BookkeepingTransactionLookupResult
    {
        if (!$this->isConfigured()) {
            return BookkeepingTransactionLookupResult::failed(self::MESSAGE_NOT_CONFIGURED);
        }

        $token = $this->connection->accessToken();
        if ($token === null) {
            return BookkeepingTransactionLookupResult::failed(self::MESSAGE_NO_ACCESS_TOKEN);
        }

        return $this->lookUpJournalEntry($reference, $token);
    }

    /**
     * Split out of getTransaction() purely to keep its own return count
     * within SonarCloud's php:S1142 limit (3) — the configured/token guard
     * clauses above already cover the two precondition failures, so this
     * only needs to cover the lookup's own outcomes.
     */
    private function lookUpJournalEntry(string $reference, string $token): BookkeepingTransactionLookupResult
    {
        try {
            $entry = $this->findJournalEntry($reference, $token);
            return $entry === null
                ? BookkeepingTransactionLookupResult::notFound()
                : BookkeepingTransactionLookupResult::found($entry['Id']);
        } catch (GuzzleException|\JsonException $e) {
            $this->logger->warning('QuickBooks getTransaction failed.', $this->errorLogContext($e));
            return BookkeepingTransactionLookupResult::failed($e->getMessage());
        }
    }

    #[\Override]
    public function deleteTransaction(string $reference): BookkeepingResult
    {
        if (!$this->isConfigured()) {
            return new BookkeepingResult(false, message: self::MESSAGE_NOT_CONFIGURED);
        }

        $token = $this->connection->accessToken();
        if ($token === null) {
            return new BookkeepingResult(false, message: self::MESSAGE_NO_ACCESS_TOKEN);
        }

        return $this->postJournalEntryDeletion($reference, $token);
    }

    /**
     * Split out of deleteTransaction() purely to keep its own return count
     * within SonarCloud's php:S1142 limit (3) — the configured/token guard
     * clauses above all happen before any network request, so this only
     * needs to cover the lookup-then-delete's own outcomes.
     */
    private function postJournalEntryDeletion(string $reference, string $token): BookkeepingResult
    {
        try {
            $entry = $this->findJournalEntry($reference, $token);
            if ($entry === null) {
                return new BookkeepingResult(false, message: 'No QuickBooks JournalEntry found for reference ' . $reference . '.');
            }

            $response = $this->httpClient->post($this->connection->companyUrl() . self::PATH_JOURNAL_ENTRY, [
                'headers' => $this->connection->authHeaders($token),
                'query' => ['operation' => 'delete'],
                'json' => ['Id' => $entry['Id'], 'SyncToken' => $entry['SyncToken']],
            ]);
            /** @var array{JournalEntry?: array{Id?: string}} $data */
            $data = json_decode((string) $response->getBody(), true, 512, JSON_THROW_ON_ERROR);
            return new BookkeepingResult(true, $data['JournalEntry']['Id'] ?? $entry['Id']);
        } catch (GuzzleException|\JsonException $e) {
            $this->logger->error('QuickBooks deleteTransaction failed.', $this->errorLogContext($e));
            return new BookkeepingResult(false, message: $e->getMessage());
        }
    }

    /**
     * @param list<BookkeepingLine> $lines
     * @return list<array<string, mixed>>|string A built QBO Line array, or an error message when a role has no configured account.
     */
    private function buildLines(array $lines): array|string
    {
        $built = [];
        foreach ($lines as $line) {
            $accountId = $this->accountIdForRole($line->account);
            if ($accountId === null) {
                return 'No QuickBooks account configured for role ' . $line->account->value . '.';
            }
            $built[] = [
                'Description' => $line->description ?? '',
                'Amount' => $line->amount,
                'DetailType' => 'JournalEntryLineDetail',
                'JournalEntryLineDetail' => [
                    'PostingType' => $line->isDebit() ? 'Debit' : 'Credit',
                    'AccountRef' => ['value' => $accountId],
                ],
            ];
        }
        return $built;
    }

    /**
     * @param list<array<string, mixed>> $lines
     * @return array<string, mixed>
     */
    private function journalEntryPayload(BookkeepingTransaction $transaction, array $lines): array
    {
        return [
            'DocNumber' => substr($transaction->getReference(), 0, 21),
            'TxnDate' => $transaction->getDate()->format('Y-m-d'),
            'CurrencyRef' => ['value' => $transaction->getCurrency()],
            'Line' => $lines,
        ];
    }

    /**
     * Looks up a JournalEntry by this app's own reference, stored in QBO's
     * `DocNumber` (queryable, unlike a custom field) — truncated to 21
     * characters to match `journalEntryPayload()`'s own truncation, since
     * that's QBO's own DocNumber length limit.
     *
     * @return array{Id: string, SyncToken: string}|null
     * @throws GuzzleException|\JsonException
     */
    private function findJournalEntry(string $reference, string $token): ?array
    {
        $docNumber = substr($reference, 0, 21);
        $query = "SELECT Id, SyncToken FROM JournalEntry WHERE DocNumber = '" . str_replace("'", "\\'", $docNumber) . "'";

        $response = $this->httpClient->get($this->connection->companyUrl() . '/query', [
            'headers' => $this->connection->authHeaders($token),
            'query' => ['query' => $query],
        ]);
        /** @var array{QueryResponse?: array{JournalEntry?: list<array{Id?: string, SyncToken?: string}>}} $data */
        $data = json_decode((string) $response->getBody(), true, 512, JSON_THROW_ON_ERROR);
        $entry = $data['QueryResponse']['JournalEntry'][0] ?? null;
        if ($entry === null || !isset($entry['Id'], $entry['SyncToken'])) {
            return null;
        }

        return ['Id' => $entry['Id'], 'SyncToken' => $entry['SyncToken']];
    }

    private function accountIdForRole(AccountRole $role): ?string
    {
        $value = $this->settings->getSetting(self::ACCOUNT_KEY_PREFIX . $role->value);
        return $value !== '' ? $value : null;
    }

    /**
     * @return array<string, mixed>
     */
    private function errorLogContext(GuzzleException|\JsonException $e): array
    {
        $context = ['error' => $e->getMessage()];
        $response = $e instanceof RequestException ? $e->getResponse() : null;
        if ($response !== null) {
            try {
                /** @var array{Fault?: array{Error?: list<array{Message?: string, Detail?: string, code?: string}>}} $data */
                $data = json_decode((string) $response->getBody(), true, 512, JSON_THROW_ON_ERROR);
                $context['quickbooks_error'] = $data['Fault']['Error'][0] ?? null;
            } catch (\JsonException) {
                // Response body wasn't JSON -- $context['error'] above already has the exception's own message.
            }
        }
        return $context;
    }
}
