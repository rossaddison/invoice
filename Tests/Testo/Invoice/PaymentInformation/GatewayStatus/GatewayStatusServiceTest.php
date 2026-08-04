<?php

declare(strict_types=1);

namespace Tests\Testo\Invoice\PaymentInformation\GatewayStatus;

use App\Infrastructure\Persistence\GatewayStatus\GatewayStatus;
use App\Invoice\PaymentInformation\GatewayStatus\GatewayStatusRepository;
use App\Invoice\PaymentInformation\GatewayStatus\GatewayStatusRow;
use App\Invoice\PaymentInformation\GatewayStatus\GatewayStatusService;
use Mockery as m;
use Testo\Assert;
use Testo\Test;
use Yiisoft\Aliases\Aliases;

/**
 * Covers GatewayStatusService: JSON round-trip through gateways.json, the
 * version-bump-only-when-changed logic against composer.lock, and syncing
 * rows into the gateway_status SQLite database via the repository.
 *
 * Exercises real file I/O against a scratch directory (matching
 * DatabaseBackupServiceTest's approach for this same class of
 * inherently-file-based service) rather than mocking file_get_contents —
 * this codebase has no filesystem abstraction to inject instead.
 */
#[Test]
final class GatewayStatusServiceTest
{
    private function makeTempRoot(): string
    {
        $root = sys_get_temp_dir() . '/gateway_status_test_' . uniqid('', true);
        Assert::true(mkdir($root . '/resources/gateway-status', 0777, true));
        return $root;
    }

    private function removeTempRoot(string $root): void
    {
        $found = glob($root . '/resources/gateway-status/*');
        $files = $found === false ? [] : $found;
        foreach ($files as $file) {
            unlink($file);
        }
        if (is_file($root . '/composer.lock')) {
            unlink($root . '/composer.lock');
        }
        rmdir($root . '/resources/gateway-status');
        rmdir($root . '/resources');
        rmdir($root);
    }

    /**
     * @param array<string, mixed> $gatewaysJson
     */
    private function writeGatewaysJson(string $root, array $gatewaysJson): void
    {
        file_put_contents(
            $root . '/resources/gateway-status/gateways.json',
            json_encode($gatewaysJson, JSON_THROW_ON_ERROR),
        );
    }

    /**
     * @param array<string, string> $packageVersions
     */
    private function writeComposerLock(string $root, array $packageVersions): void
    {
        $packages = [];
        foreach ($packageVersions as $name => $version) {
            $packages[] = ['name' => $name, 'version' => $version];
        }
        file_put_contents(
            $root . '/composer.lock',
            json_encode(['packages' => $packages], JSON_THROW_ON_ERROR),
        );
    }

    public function loadFromJsonParsesEveryFieldAndRegionsList(): void
    {
        $root = $this->makeTempRoot();
        try {
            $this->writeGatewaysJson($root, [
                'schema_version' => 1,
                'gateways' => [
                    [
                        'key' => 'stripe',
                        'name' => 'Stripe',
                        'composer_package' => 'stripe/stripe-php',
                        'sdk_version' => 'v21.1.1',
                        'last_updated' => '2026-08-04',
                        'sandbox_env_var' => 'STRIPE_SANDBOX_SECRET_KEY',
                        'sandbox_tested_at' => null,
                        'sandbox_status' => null,
                        'sandbox_last_error' => null,
                        'live_tested_at' => null,
                        'regions' => ['europe', 'asia'],
                        'notes' => 'test note',
                    ],
                ],
            ]);

            $service = new GatewayStatusService(
                new Aliases(['@root' => $root]),
                m::mock(GatewayStatusRepository::class),
            );

            $rows = $service->loadFromJson();

            Assert::same(1, count($rows));
            Assert::same('stripe', $rows[0]->key);
            Assert::same('Stripe', $rows[0]->name);
            Assert::same('stripe/stripe-php', $rows[0]->composerPackage);
            Assert::same('v21.1.1', $rows[0]->sdkVersion);
            Assert::same(['europe', 'asia'], $rows[0]->regions);
            Assert::same('test note', $rows[0]->notes);
        } finally {
            $this->removeTempRoot($root);
        }
    }

    public function saveToJsonThenLoadFromJsonRoundTrips(): void
    {
        $root = $this->makeTempRoot();
        try {
            $this->writeGatewaysJson($root, ['schema_version' => 1, 'gateways' => []]);

            $service = new GatewayStatusService(
                new Aliases(['@root' => $root]),
                m::mock(GatewayStatusRepository::class),
            );

            $original = new GatewayStatusRow(
                key: 'mollie',
                name: 'Mollie',
                composerPackage: 'mollie/mollie-api-php',
                sdkVersion: 'v3.13.1',
                lastUpdated: '2026-08-04',
                sandboxEnvVar: 'MOLLIE_SANDBOX_API_KEY',
                sandboxTestedAt: null,
                sandboxStatus: null,
                sandboxLastError: null,
                liveTestedAt: null,
                regions: ['europe'],
                notes: null,
            );

            $service->saveToJson([$original]);
            $reloaded = $service->loadFromJson();

            Assert::same(1, count($reloaded));
            Assert::same($original->toArray(), $reloaded[0]->toArray());
        } finally {
            $this->removeTempRoot($root);
        }
    }

    public function resolveVersionsBumpsSdkVersionAndLastUpdatedOnlyWhenChanged(): void
    {
        $root = $this->makeTempRoot();
        try {
            $this->writeComposerLock($root, [
                'stripe/stripe-php' => 'v21.1.1',
                'mollie/mollie-api-php' => 'v3.13.1',
            ]);

            $service = new GatewayStatusService(
                new Aliases(['@root' => $root]),
                m::mock(GatewayStatusRepository::class),
            );

            $changed = new GatewayStatusRow(
                key: 'stripe',
                name: 'Stripe',
                composerPackage: 'stripe/stripe-php',
                sdkVersion: 'v20.0.0',
                lastUpdated: '2026-01-01',
                sandboxEnvVar: null,
                sandboxTestedAt: null,
                sandboxStatus: null,
                sandboxLastError: null,
                liveTestedAt: null,
                regions: [],
                notes: null,
            );
            $unchanged = new GatewayStatusRow(
                key: 'mollie',
                name: 'Mollie',
                composerPackage: 'mollie/mollie-api-php',
                sdkVersion: 'v3.13.1',
                lastUpdated: '2026-01-01',
                sandboxEnvVar: null,
                sandboxTestedAt: null,
                sandboxStatus: null,
                sandboxLastError: null,
                liveTestedAt: null,
                regions: [],
                notes: null,
            );

            $resolved = $service->resolveVersionsFromComposerLock([$changed, $unchanged]);

            Assert::same('v21.1.1', $resolved[0]->sdkVersion);
            Assert::same((new \DateTimeImmutable())->format('Y-m-d'), $resolved[0]->lastUpdated);

            Assert::same('v3.13.1', $resolved[1]->sdkVersion);
            Assert::same('2026-01-01', $resolved[1]->lastUpdated);
        } finally {
            $this->removeTempRoot($root);
        }
    }

    public function resolveVersionsLeavesRowsWithNoComposerPackageUntouched(): void
    {
        $root = $this->makeTempRoot();
        try {
            $this->writeComposerLock($root, []);

            $service = new GatewayStatusService(
                new Aliases(['@root' => $root]),
                m::mock(GatewayStatusRepository::class),
            );

            $row = new GatewayStatusRow(
                key: 'bacs',
                name: 'BACS Direct Debit',
                composerPackage: null,
                sdkVersion: null,
                lastUpdated: '2026-01-01',
                sandboxEnvVar: null,
                sandboxTestedAt: null,
                sandboxStatus: null,
                sandboxLastError: null,
                liveTestedAt: null,
                regions: ['europe'],
                notes: null,
            );

            $resolved = $service->resolveVersionsFromComposerLock([$row]);

            Assert::same($row, $resolved[0]);
        } finally {
            $this->removeTempRoot($root);
        }
    }

    public function syncToDatabaseCreatesNewEntityWhenNoneExistsForGatewayKey(): void
    {
        $root = $this->makeTempRoot();
        try {
            /** @var GatewayStatusRepository&m\MockInterface $repository */
            $repository = m::mock(GatewayStatusRepository::class);
            /** @var \Mockery\Expectation $eFind */
            $eFind = $repository->shouldReceive('findByGatewayKeyquery');
            $eFind->once()->with('stripe')->andReturn(null);
            /** @var \Mockery\Expectation $eSave */
            $eSave = $repository->shouldReceive('save');
            $eSave->once()->with(m::on(static function (GatewayStatus $entity): bool {
                return $entity->getGatewayKey() === 'stripe'
                    && $entity->getName() === 'Stripe'
                    && $entity->getRegions() === 'europe,asia';
            }));

            $service = new GatewayStatusService(new Aliases(['@root' => $root]), $repository);

            $row = new GatewayStatusRow(
                key: 'stripe',
                name: 'Stripe',
                composerPackage: 'stripe/stripe-php',
                sdkVersion: 'v21.1.1',
                lastUpdated: '2026-08-04',
                sandboxEnvVar: null,
                sandboxTestedAt: null,
                sandboxStatus: null,
                sandboxLastError: null,
                liveTestedAt: null,
                regions: ['europe', 'asia'],
                notes: null,
            );

            $service->syncToDatabase([$row]);
        } finally {
            $this->removeTempRoot($root);
        }
    }

    public function syncToDatabaseUpdatesExistingEntityWhenOneExists(): void
    {
        $root = $this->makeTempRoot();
        try {
            $existing = new GatewayStatus();
            $existing->setGatewayKey('mollie');
            $existing->setName('Mollie');

            /** @var GatewayStatusRepository&m\MockInterface $repository */
            $repository = m::mock(GatewayStatusRepository::class);
            /** @var \Mockery\Expectation $eFind */
            $eFind = $repository->shouldReceive('findByGatewayKeyquery');
            $eFind->once()->with('mollie')->andReturn($existing);
            /** @var \Mockery\Expectation $eSave */
            $eSave = $repository->shouldReceive('save');
            $eSave->once()->with($existing);

            $service = new GatewayStatusService(new Aliases(['@root' => $root]), $repository);

            $row = new GatewayStatusRow(
                key: 'mollie',
                name: 'Mollie',
                composerPackage: 'mollie/mollie-api-php',
                sdkVersion: 'v3.13.1',
                lastUpdated: '2026-08-04',
                sandboxEnvVar: 'MOLLIE_SANDBOX_API_KEY',
                sandboxTestedAt: '2026-08-04',
                sandboxStatus: 'pass',
                sandboxLastError: null,
                liveTestedAt: null,
                regions: ['europe'],
                notes: null,
            );

            $service->syncToDatabase([$row]);

            Assert::same('v3.13.1', $existing->getSdkVersion());
            Assert::same('pass', $existing->getSandboxStatus());
            Assert::same('europe', $existing->getRegions());
        } finally {
            $this->removeTempRoot($root);
        }
    }
}
