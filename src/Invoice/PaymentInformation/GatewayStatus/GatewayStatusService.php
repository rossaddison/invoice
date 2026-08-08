<?php

declare(strict_types=1);

namespace App\Invoice\PaymentInformation\GatewayStatus;

use App\Infrastructure\Persistence\GatewayStatus\GatewayStatus;
use DateTimeImmutable;
use Yiisoft\Aliases\Aliases;

/**
 * Reads/writes resources/gateway-status/gateways.json (the human-edited
 * source of truth — see docs/GATEWAY_STATUS_PAGE_AUGUST_2026.md), resolves
 * SDK versions from composer.lock, and syncs rows into the gateway_status
 * SQLite database via GatewayStatusRepository. The database is a projection
 * of the JSON file, never edited independently of it.
 */
final class GatewayStatusService
{
    public function __construct(
        private readonly Aliases $aliases,
        private readonly GatewayStatusRepository $repository,
    ) {
    }

    /**
     * @return list<GatewayStatusRow>
     */
    public function loadFromJson(): array
    {
        $path = $this->jsonPath();
        $contents = file_get_contents($path);
        if ($contents === false) {
            throw new GatewayStatusException("Unable to read {$path}");
        }

        /**
         * @var array{
         *     schema_version?: int,
         *     gateways?: list<array{
         *         key?: string,
         *         name?: string,
         *         composer_package?: string|null,
         *         sdk_version?: string|null,
         *         last_updated?: string,
         *         sandbox_env_var?: string|null,
         *         sandbox_tested_at?: string|null,
         *         sandbox_status?: string|null,
         *         sandbox_last_error?: string|null,
         *         live_tested_at?: string|null,
         *         expiry_date?: string|null,
         *         regions?: array<array-key, string>,
         *         notes?: string|null,
         *     }>,
         * } $decoded
         */
        $decoded = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);

        $rows = [];
        foreach ($decoded['gateways'] ?? [] as $gateway) {
            $rows[] = GatewayStatusRow::fromArray($gateway);
        }
        return $rows;
    }

    /**
     * @param list<GatewayStatusRow> $rows
     */
    public function saveToJson(array $rows): void
    {
        $data = [
            'schema_version' => 1,
            'gateways' => array_map(static fn (GatewayStatusRow $row): array => $row->toArray(), $rows),
        ];
        $json = json_encode($data, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        file_put_contents($this->jsonPath(), $json . "\n");
    }

    /**
     * Resolves each row's sdk_version from composer.lock's resolved package
     * version, bumping last_updated to today only when the version actually
     * changed from what's currently on file.
     *
     * @param list<GatewayStatusRow> $rows
     * @return list<GatewayStatusRow>
     */
    public function resolveVersionsFromComposerLock(array $rows): array
    {
        $versions = $this->readComposerLockVersions();
        $today = (new DateTimeImmutable())->format('Y-m-d');

        return array_map(
            function (GatewayStatusRow $row) use ($versions, $today): GatewayStatusRow {
                if ($row->composerPackage === null) {
                    return $row;
                }
                $resolved = $versions[$row->composerPackage] ?? null;
                if ($resolved === null || $resolved === $row->sdkVersion) {
                    return $row;
                }
                return $row->withSdkVersion($resolved, $today);
            },
            $rows,
        );
    }

    /**
     * Upserts every row into the gateway_status SQLite database, matching
     * existing entities by gateway_key.
     *
     * @param list<GatewayStatusRow> $rows
     */
    public function syncToDatabase(array $rows): void
    {
        foreach ($rows as $row) {
            $entity = $this->repository->findByGatewayKeyquery($row->key) ?? new GatewayStatus();
            $entity->setGatewayKey($row->key);
            $entity->setName($row->name);
            $entity->setSdkVersion($row->sdkVersion);
            $entity->setLastUpdated($row->lastUpdated);
            $entity->setSandboxTestedAt($row->sandboxTestedAt);
            $entity->setSandboxStatus($row->sandboxStatus);
            $entity->setSandboxLastError($row->sandboxLastError);
            $entity->setLiveTestedAt($row->liveTestedAt);
            $entity->setExpiryDate($row->expiryDate);
            $entity->setRegions(implode(',', $row->regions));
            $entity->setNotes($row->notes);
            $this->repository->save($entity);
        }
    }

    /**
     * @return array<string, string>
     */
    private function readComposerLockVersions(): array
    {
        $path = $this->aliases->get('@root') . '/composer.lock';
        $contents = file_get_contents($path);
        if ($contents === false) {
            throw new GatewayStatusException("Unable to read {$path}");
        }

        /** @var array{packages?: list<array{name?: string, version?: string}>} $decoded */
        $decoded = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);

        $versions = [];
        foreach ($decoded['packages'] ?? [] as $package) {
            if (isset($package['name'], $package['version'])) {
                $versions[$package['name']] = $package['version'];
            }
        }
        return $versions;
    }

    private function jsonPath(): string
    {
        return $this->aliases->get('@root') . '/resources/gateway-status/gateways.json';
    }
}
