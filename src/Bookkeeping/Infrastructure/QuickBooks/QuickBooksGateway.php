<?php

declare(strict_types=1);

namespace App\Bookkeeping\Infrastructure\QuickBooks;

use App\Auth\Client\Intuit;
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
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Log\LoggerInterface;
use Yiisoft\Factory\Factory;
use Yiisoft\Session\Session;
use Yiisoft\Yii\AuthClient\OAuthToken;
use Yiisoft\Yii\AuthClient\StateStorage\DummyStateStorage;

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
    private const string SANDBOX_BASE_URL = 'https://sandbox-quickbooks.api.intuit.com';
    private const string PRODUCTION_BASE_URL = 'https://quickbooks.api.intuit.com';

    private const string KEY_CLIENT_ID = 'bookkeeping_quickbooks_client_id';
    private const string KEY_CLIENT_SECRET = 'bookkeeping_quickbooks_client_secret';
    private const string KEY_REALM_ID = 'bookkeeping_quickbooks_realm_id';
    private const string KEY_REFRESH_TOKEN = 'bookkeeping_quickbooks_refresh_token';
    private const string KEY_ACCESS_TOKEN = 'bookkeeping_quickbooks_access_token';
    private const string KEY_ACCESS_TOKEN_EXPIRES_AT = 'bookkeeping_quickbooks_access_token_expires_at';
    private const string KEY_SANDBOX = 'bookkeeping_quickbooks_sandbox';
    private const string ACCOUNT_KEY_PREFIX = 'bookkeeping_quickbooks_account_';

    private const string PATH_JOURNAL_ENTRY = '/journalentry';
    private const string CONTENT_TYPE_JSON = 'application/json';
    private const string MESSAGE_NOT_CONFIGURED = 'QuickBooks is not configured.';
    private const string MESSAGE_NO_ACCESS_TOKEN = 'Unable to obtain a QuickBooks access token.';

    private readonly Intuit $intuit;

    /**
     * $authHttpClient/$requestFactory/$factory are plain framework
     * services (already safely injectable in both web and console
     * context) used only to build $intuit -- Intuit::class itself is
     * deliberately NOT container-bound here, since yiisoft/config's
     * Merger throws a hard error on a duplicate top-level key across a
     * group's source files, and config/web/di/yii-auth-client.php
     * already binds Intuit::class for the web app's own "Connect to
     * QuickBooks" flow (App\Bookkeeping\Controller\
     * QuickBooksConnectController). Building this gateway's own instance
     * directly here means it works from a console command with no
     * shared binding at all, matching every other gateway class in this
     * app's `new HttpClient()`-as-default-param convention for
     * collaborators DI can't cleanly express.
     *
     * DummyStateStorage/Session are real no-op-for-our-purposes
     * instances, never a plain default param value here (this
     * constructor body runs at object-construction time, not in a
     * compile-time default-value context) -- refreshAccessToken() itself
     * touches neither, confirmed by reading its body directly in the
     * fork.
     */
    public function __construct(
        private readonly SettingRepository $settings,
        private readonly LoggerInterface $logger,
        ClientInterface $authHttpClient,
        RequestFactoryInterface $requestFactory,
        Factory $factory,
        private readonly HttpClient $httpClient = new HttpClient(),
    ) {
        $this->intuit = new Intuit($authHttpClient, $requestFactory, new DummyStateStorage(), $factory, new Session());
    }

    #[\Override]
    public function getDriverKey(): string
    {
        return 'quickbooks';
    }

    #[\Override]
    public function isConfigured(): bool
    {
        return $this->clientId() !== ''
            && $this->clientSecret() !== ''
            && $this->realmId() !== ''
            && $this->storedRefreshToken() !== '';
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

        $token = $this->accessToken();
        if ($token === null) {
            return new BookkeepingResult(false, message: self::MESSAGE_NO_ACCESS_TOKEN);
        }

        try {
            $response = $this->httpClient->post($this->companyUrl() . self::PATH_JOURNAL_ENTRY, [
                'headers' => $this->authHeaders($token),
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

        $token = $this->accessToken();
        if ($token === null) {
            return new BookkeepingResult(false, message: self::MESSAGE_NO_ACCESS_TOKEN);
        }

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

            $response = $this->httpClient->post($this->companyUrl() . self::PATH_JOURNAL_ENTRY, [
                'headers' => $this->authHeaders($token),
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

        $token = $this->accessToken();
        if ($token === null) {
            return BookkeepingTransactionLookupResult::failed(self::MESSAGE_NO_ACCESS_TOKEN);
        }

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

        $token = $this->accessToken();
        if ($token === null) {
            return new BookkeepingResult(false, message: self::MESSAGE_NO_ACCESS_TOKEN);
        }

        try {
            $entry = $this->findJournalEntry($reference, $token);
            if ($entry === null) {
                return new BookkeepingResult(false, message: 'No QuickBooks JournalEntry found for reference ' . $reference . '.');
            }

            $response = $this->httpClient->post($this->companyUrl() . self::PATH_JOURNAL_ENTRY, [
                'headers' => $this->authHeaders($token),
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

        $response = $this->httpClient->get($this->companyUrl() . '/query', [
            'headers' => $this->authHeaders($token),
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

    /**
     * @return array<string, string>
     */
    private function authHeaders(string $token): array
    {
        return [
            'Authorization' => 'Bearer ' . $token,
            'Content-Type' => self::CONTENT_TYPE_JSON,
            'Accept' => self::CONTENT_TYPE_JSON,
        ];
    }

    private function companyUrl(): string
    {
        return $this->baseUrl() . '/v3/company/' . rawurlencode($this->realmId());
    }

    private function baseUrl(): string
    {
        return $this->settings->getSetting(self::KEY_SANDBOX) === '1'
            ? self::SANDBOX_BASE_URL
            : self::PRODUCTION_BASE_URL;
    }

    private function accountIdForRole(AccountRole $role): ?string
    {
        $value = $this->settings->getSetting(self::ACCOUNT_KEY_PREFIX . $role->value);
        return $value !== '' ? $value : null;
    }

    /**
     * Returns a cached access token when still valid for more than 60s,
     * otherwise refreshes (and persists the rotated pair) first.
     */
    private function accessToken(): ?string
    {
        $cached = $this->settings->getSetting(self::KEY_ACCESS_TOKEN);
        $expiresAt = (int) $this->settings->getSetting(self::KEY_ACCESS_TOKEN_EXPIRES_AT);
        if ($cached !== '' && $expiresAt - time() > 60) {
            return (string) $this->settings->decode($cached);
        }

        return $this->refreshAccessToken();
    }

    private function refreshAccessToken(): ?string
    {
        $refreshToken = $this->storedRefreshToken();
        if ($refreshToken === '') {
            return null;
        }

        $this->intuit->setClientId($this->clientId());
        $this->intuit->setClientSecret($this->clientSecret());
        $requestToken = new OAuthToken();
        $requestToken->setParam('refresh_token', $refreshToken);

        try {
            $newToken = $this->intuit->refreshAccessToken($requestToken);
        } catch (\Psr\Http\Client\ClientExceptionInterface $e) {
            $this->logger->error('QuickBooks token refresh failed.', ['error' => $e->getMessage()]);
            return null;
        }

        $accessToken = $newToken->getParam('access_token');
        $newRefreshToken = $newToken->getParam('refresh_token');
        $expiresIn = $newToken->getParam('expires_in');
        if (!is_string($accessToken) || $accessToken === ''
            || !is_string($newRefreshToken) || $newRefreshToken === ''
            || !is_numeric($expiresIn)
        ) {
            $this->logger->error('QuickBooks token refresh: unexpected response shape.');
            return null;
        }

        $this->persistTokens($accessToken, $newRefreshToken, (int) $expiresIn);
        return $accessToken;
    }

    private function persistTokens(string $accessToken, string $refreshToken, int $expiresIn): void
    {
        $this->persistSetting(self::KEY_ACCESS_TOKEN, (string) $this->settings->encode($accessToken));
        $this->persistSetting(self::KEY_ACCESS_TOKEN_EXPIRES_AT, (string) (time() + $expiresIn));
        $this->persistSetting(self::KEY_REFRESH_TOKEN, (string) $this->settings->encode($refreshToken));
    }

    private function persistSetting(string $key, string $value): void
    {
        $setting = $this->settings->withKey($key) ?? new Setting(setting_key: $key);
        $setting->setSettingValue($value);
        $this->settings->save($setting);
    }

    private function clientId(): string
    {
        return $this->settings->getSetting(self::KEY_CLIENT_ID);
    }

    private function clientSecret(): string
    {
        $encoded = $this->settings->getSetting(self::KEY_CLIENT_SECRET);
        return $encoded !== '' ? (string) $this->settings->decode($encoded) : '';
    }

    private function realmId(): string
    {
        return $this->settings->getSetting(self::KEY_REALM_ID);
    }

    private function storedRefreshToken(): string
    {
        $encoded = $this->settings->getSetting(self::KEY_REFRESH_TOKEN);
        return $encoded !== '' ? (string) $this->settings->decode($encoded) : '';
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
