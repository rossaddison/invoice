<?php

declare(strict_types=1);

namespace Tests\Testo\Bookkeeping\Infrastructure\FrontAccounting;

use App\Bookkeeping\Infrastructure\FrontAccounting\FrontAccountingLookup;
use App\Bookkeeping\Infrastructure\FrontAccounting\FrontAccountingLookupKind;
use App\Bookkeeping\Infrastructure\FrontAccounting\FrontAccountingLookupRepository;
use App\Bookkeeping\Infrastructure\FrontAccounting\FrontAccountingLookupSyncService;
use App\Invoice\Setting\SettingRepository;
use DateTimeImmutable;
use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Exception\ConnectException;
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
 * Guzzle is mocked (MockHandler): no real network. Mirrors
 * FrontAccountingGatewayTest's own shape/conventions.
 */
#[Test]
final class FrontAccountingLookupSyncServiceTest
{
    private ?\ArrayObject $history = null;

    /**
     * @param array<string, string> $overrides
     */
    private function makeSettings(array $overrides = []): SettingRepository
    {
        $values = array_merge([
            'bookkeeping_frontaccounting_base_url' => 'http://localhost/modules/api',
            'bookkeeping_frontaccounting_company' => '0',
            'bookkeeping_frontaccounting_username' => 'admin',
            'bookkeeping_frontaccounting_password' => 'enc:secret',
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

    private function makeLogger(): LoggerInterface
    {
        /** @var LoggerInterface&m\MockInterface $logger */
        $logger = m::spy(LoggerInterface::class);

        return $logger;
    }

    private function json(mixed $data): Response
    {
        return new Response(200, [], json_encode($data, JSON_THROW_ON_ERROR));
    }

    /**
     * @param list<Response|ConnectException> $responses
     */
    private function makeService(
        array $responses,
        ?FrontAccountingLookupRepository $repository = null,
        ?SettingRepository $settings = null,
    ): FrontAccountingLookupSyncService {
        $log = new \ArrayObject();
        $this->history = $log;
        $mock = new MockHandler($responses);
        $stack = HandlerStack::create($mock);
        $stack->push(Middleware::history($log));

        /** @var FrontAccountingLookupRepository&m\MockInterface $repo */
        $repo = $repository ?? m::mock(FrontAccountingLookupRepository::class);
        if ($repository === null) {
            $repo->shouldReceive('findOneByKindAndExternalId')->andReturn(null);
            $repo->shouldReceive('save');
        }

        return new FrontAccountingLookupSyncService(
            $settings ?? $this->makeSettings(),
            $repo,
            $this->makeLogger(),
            new HttpClient(['handler' => $stack]),
        );
    }

    private function requestAt(int $index): RequestInterface
    {
        /** @var array{request: RequestInterface} $entry */
        $entry = ($this->history ?? throw new \LogicException('No service was built.'))[$index];
        return $entry['request'];
    }

    public function notConfiguredReturnsAZeroSummaryWithoutMakingAnyRequest(): void
    {
        $service = $this->makeService([], settings: $this->makeSettings(['bookkeeping_frontaccounting_base_url' => '']));

        $summary = $service->sync();

        Assert::same(0, $summary->syncedCount);
        Assert::count($summary->messages, 1);
    }

    public function syncsAllFourKindsAgainstTheirOwnEndpointsInOrder(): void
    {
        $service = $this->makeService([
            $this->json([['id' => '1', 'name' => 'Tax', 'inactive' => '0']]), // taxgroups page 1
            $this->json([]), // taxgroups page 2 -- stop
            $this->json([['stock_id' => '301', 'description' => 'Service Item']]), // inventory page 1
            $this->json([]), // inventory page 2 -- stop
            $this->json([['id' => '1', 'bank_account_name' => 'Main Account']]), // bankaccounts, not paginated
            $this->json([['id' => '3', 'name' => 'Cash']]), // paymentterms page 1
            $this->json([]), // paymentterms page 2 -- stop
        ]);

        $summary = $service->sync();

        Assert::same(4, $summary->syncedCount);
        Assert::count($summary->messages, 0);
        Assert::same('/modules/api/taxgroups/?page=1', $this->requestAt(0)->getUri()->getPath() . '?' . $this->requestAt(0)->getUri()->getQuery());
        Assert::same('/modules/api/inventory/?page=1', $this->requestAt(2)->getUri()->getPath() . '?' . $this->requestAt(2)->getUri()->getQuery());
        Assert::same('/modules/api/bankaccounts', $this->requestAt(4)->getUri()->getPath());
        Assert::same('/modules/api/paymentterms/?page=1', $this->requestAt(5)->getUri()->getPath() . '?' . $this->requestAt(5)->getUri()->getQuery());
        Assert::same('admin', $this->requestAt(0)->getHeaderLine('X-USER'));
        Assert::same('secret', $this->requestAt(0)->getHeaderLine('X-PASSWORD'));
    }

    public function paginatesAKindUntilAnEmptyPageIsReturned(): void
    {
        $service = $this->makeService([
            $this->json([['id' => '1', 'name' => 'A']]), // taxgroups page 1
            $this->json([['id' => '2', 'name' => 'B']]), // taxgroups page 2
            $this->json([]), // taxgroups page 3 -- stop
            $this->json([]), // inventory page 1 -- stop
            $this->json([]), // bankaccounts
            $this->json([]), // paymentterms page 1 -- stop
        ]);

        $summary = $service->sync();

        Assert::same(2, $summary->syncedCount);
        Assert::same('page=1', $this->requestAt(0)->getUri()->getQuery());
        Assert::same('page=2', $this->requestAt(1)->getUri()->getQuery());
        Assert::same('page=3', $this->requestAt(2)->getUri()->getQuery());
    }

    public function insertsANewLookupWhenNoExistingRowMatches(): void
    {
        /** @var FrontAccountingLookupRepository&m\MockInterface $repo */
        $repo = m::mock(FrontAccountingLookupRepository::class);
        $repo->shouldReceive('findOneByKindAndExternalId')->andReturn(null);
        $repo->shouldReceive('save')
            ->once()
            ->with(m::on(static function (FrontAccountingLookup $lookup): bool {
                return $lookup->getKind() === FrontAccountingLookupKind::BankAccount
                    && $lookup->getExternalId() === '1'
                    && $lookup->getLabel() === 'Main Account';
            }));

        $service = $this->makeService([
            $this->json([]), // taxgroups
            $this->json([]), // inventory
            $this->json([['id' => '1', 'bank_account_name' => 'Main Account']]), // bankaccounts
            $this->json([]), // paymentterms
        ], repository: $repo);

        $service->sync();
    }

    public function updatesTheLabelAndSyncedAtOfAnExistingLookupInstead(): void
    {
        $existing = new FrontAccountingLookup(
            FrontAccountingLookupKind::BankAccount->value,
            '1',
            'Old Name',
            new DateTimeImmutable('2020-01-01'),
        );
        $existing->setId(9);

        /** @var FrontAccountingLookupRepository&m\MockInterface $repo */
        $repo = m::mock(FrontAccountingLookupRepository::class);
        $repo->shouldReceive('findOneByKindAndExternalId')->andReturn($existing);
        $repo->shouldReceive('save')->once()->with($existing);

        $service = $this->makeService([
            $this->json([]),
            $this->json([]),
            $this->json([['id' => '1', 'bank_account_name' => 'New Name']]),
            $this->json([]),
        ], repository: $repo);

        $service->sync();

        Assert::same('New Name', $existing->getLabel());
        Assert::true($existing->getSyncedAt() > new DateTimeImmutable('2020-01-02'));
    }

    public function recordsAWarningMessageAndStillSyncsTheRemainingKindsWhenOneFails(): void
    {
        $service = $this->makeService([
            new ConnectException('Connection refused', new Psr7Request('GET', '/taxgroups/')), // taxgroups
            $this->json([]), // inventory
            $this->json([['id' => '1', 'bank_account_name' => 'Main Account']]), // bankaccounts
            $this->json([]), // paymentterms
        ]);

        $summary = $service->sync();

        Assert::same(1, $summary->syncedCount);
        Assert::count($summary->messages, 1);
        Assert::true(str_starts_with($summary->messages[0], 'tax_group:'));
    }
}
