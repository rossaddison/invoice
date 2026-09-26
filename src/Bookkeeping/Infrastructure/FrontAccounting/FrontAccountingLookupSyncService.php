<?php

declare(strict_types=1);

namespace App\Bookkeeping\Infrastructure\FrontAccounting;

use App\Invoice\Libraries\CryptorException;
use App\Invoice\Setting\SettingRepository;
use DateTimeImmutable;
use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Log\LoggerInterface;

/**
 * Refreshes the FrontAccountingLookup cache from the FrontAccountingSimpleAPI
 * fork's own small reference-list endpoints, so the Online Bookkeeping
 * settings tab's FrontAccounting dropdowns (bank account, stock/VAT item,
 * tax group, payment terms) show real FrontAccounting labels instead of
 * raw ids.
 *
 * Deliberately independent of FrontAccountingGateway's own private
 * request() helper -- both read the same four
 * `bookkeeping_frontaccounting_{base_url,company,username,password}`
 * settings and build the same X-COMPANY/X-USER/X-PASSWORD request, but
 * sharing that behind one class would mean touching
 * FrontAccountingGateway's already-tested internals for this; the small
 * duplication was judged the safer trade.
 */
final class FrontAccountingLookupSyncService
{
    private const string KEY_BASE_URL = 'bookkeeping_frontaccounting_base_url';
    private const string KEY_COMPANY = 'bookkeeping_frontaccounting_company';
    private const string KEY_USERNAME = 'bookkeeping_frontaccounting_username';
    private const string KEY_PASSWORD = 'bookkeeping_frontaccounting_password';

    /**
     * Safety cap against a misbehaving server that never returns an empty
     * page -- FrontAccounting's own RESULTS_PER_PAGE can be as low as 2 in
     * a trial install, so a real chart of a few hundred items can need
     * dozens of pages.
     */
    private const int MAX_PAGES = 200;

    public function __construct(
        private readonly SettingRepository $settings,
        private readonly FrontAccountingLookupRepository $repository,
        private readonly LoggerInterface $logger,
        private readonly HttpClient $httpClient = new HttpClient(),
    ) {
    }

    public function isConfigured(): bool
    {
        foreach ([self::KEY_BASE_URL, self::KEY_COMPANY, self::KEY_USERNAME, self::KEY_PASSWORD] as $key) {
            if ($this->settings->getSetting($key) === '') {
                return false;
            }
        }

        return $this->isSafeBaseUrl($this->settings->getSetting(self::KEY_BASE_URL));
    }

    private function isSafeBaseUrl(string $baseUrl): bool
    {
        $scheme = strtolower((string) parse_url($baseUrl, PHP_URL_SCHEME));
        $host = strtolower((string) parse_url($baseUrl, PHP_URL_HOST));

        return $scheme === 'https'
            || ($scheme === 'http' && in_array($host, ['localhost', '127.0.0.1', '[::1]', '::1'], true));
    }

    public function sync(): FrontAccountingLookupSyncSummary
    {
        if (!$this->isConfigured()) {
            return new FrontAccountingLookupSyncSummary(0, ['FrontAccounting is not configured.']);
        }

        $synced = 0;
        $messages = [];

        foreach ($this->kindsToSync() as [$kind, $path, $paginated, $idField, $labelField]) {
            try {
                $rows = $paginated ? $this->fetchAllPages($path) : $this->request('GET', $path);
            } catch (GuzzleException|\JsonException|CryptorException $e) {
                $messages[] = $kind->value . ': ' . $e->getMessage();
                continue;
            }

            $synced += $this->upsertRows($kind, $rows, $idField, $labelField);
        }

        return new FrontAccountingLookupSyncSummary($synced, $messages);
    }

    /**
     * @return list<array{0: FrontAccountingLookupKind, 1: string, 2: bool, 3: string, 4: string}>
     */
    private function kindsToSync(): array
    {
        return [
            [FrontAccountingLookupKind::TaxGroup, '/taxgroups/', true, 'id', 'name'],
            [FrontAccountingLookupKind::StockItem, '/inventory/', true, 'stock_id', 'description'],
            [FrontAccountingLookupKind::BankAccount, '/bankaccounts', false, 'id', 'bank_account_name'],
            [FrontAccountingLookupKind::PaymentTerms, '/paymentterms/', true, 'id', 'name'],
        ];
    }

    /**
     * @param list<array<array-key, mixed>> $rows
     */
    private function upsertRows(FrontAccountingLookupKind $kind, array $rows, string $idField, string $labelField): int
    {
        $now = new DateTimeImmutable();
        $count = 0;

        foreach ($rows as $row) {
            if (!isset($row[$idField], $row[$labelField])) {
                continue;
            }
            $externalId = (string) $row[$idField];
            $label = (string) $row[$labelField];

            $lookup = $this->repository->findOneByKindAndExternalId($kind, $externalId);
            if ($lookup === null) {
                $lookup = new FrontAccountingLookup($kind->value, $externalId, $label, $now);
            } else {
                $lookup->setLabel($label);
                $lookup->setSyncedAt($now);
            }

            $this->repository->save($lookup);
            $count++;
        }

        return $count;
    }

    /**
     * @return list<array<array-key, mixed>>
     * @throws GuzzleException
     * @throws \JsonException
     * @throws CryptorException
     */
    private function fetchAllPages(string $path): array
    {
        $all = [];
        for ($page = 1; $page <= self::MAX_PAGES; $page++) {
            $rows = $this->request('GET', $path, ['query' => ['page' => $page]]);
            if ($rows === []) {
                break;
            }
            array_push($all, ...$rows);
        }

        return $all;
    }

    /**
     * @param array<string, mixed> $options
     * @return list<array<array-key, mixed>>
     * @throws GuzzleException
     * @throws \JsonException
     * @throws CryptorException
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

        /** @var array<array-key, mixed>|scalar|null $data */
        $data = json_decode($body, true, 512, JSON_THROW_ON_ERROR);

        /** @var list<array<array-key, mixed>> */
        return is_array($data) ? $data : [];
    }
}
