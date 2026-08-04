<?php

declare(strict_types=1);

namespace App\Invoice\PaymentInformation\GatewayStatus;

/**
 * One entry from resources/gateway-status/gateways.json. Field ownership
 * (see docs/GATEWAY_STATUS_PAGE_AUGUST_2026.md): $sdkVersion/$lastUpdated
 * are rewritten only by RebuildGatewayStatusCommand (from composer.lock);
 * $sandboxTestedAt/$sandboxStatus/$sandboxLastError only by
 * CheckGatewaySandboxesCommand; everything else is human-curated and must
 * never be touched by either command.
 */
final readonly class GatewayStatusRow
{
    /**
     * @param list<string> $regions
     */
    public function __construct(
        public string $key,
        public string $name,
        public ?string $composerPackage,
        public ?string $sdkVersion,
        public string $lastUpdated,
        public ?string $sandboxEnvVar,
        public ?string $sandboxTestedAt,
        public ?string $sandboxStatus,
        public ?string $sandboxLastError,
        public ?string $liveTestedAt,
        public array $regions,
        public ?string $notes,
    ) {
    }

    /**
     * @param array{
     *     key?: string,
     *     name?: string,
     *     composer_package?: string|null,
     *     sdk_version?: string|null,
     *     last_updated?: string,
     *     sandbox_env_var?: string|null,
     *     sandbox_tested_at?: string|null,
     *     sandbox_status?: string|null,
     *     sandbox_last_error?: string|null,
     *     live_tested_at?: string|null,
     *     regions?: array<array-key, string>,
     *     notes?: string|null,
     * } $data
     */
    public static function fromArray(array $data): self
    {
        $regions = array_values($data['regions'] ?? []);

        return new self(
            key: $data['key'] ?? '',
            name: $data['name'] ?? '',
            composerPackage: $data['composer_package'] ?? null,
            sdkVersion: $data['sdk_version'] ?? null,
            lastUpdated: $data['last_updated'] ?? '',
            sandboxEnvVar: $data['sandbox_env_var'] ?? null,
            sandboxTestedAt: $data['sandbox_tested_at'] ?? null,
            sandboxStatus: $data['sandbox_status'] ?? null,
            sandboxLastError: $data['sandbox_last_error'] ?? null,
            liveTestedAt: $data['live_tested_at'] ?? null,
            regions: $regions,
            notes: $data['notes'] ?? null,
        );
    }

    /**
     * @return array{
     *     key: string,
     *     name: string,
     *     composer_package: string|null,
     *     sdk_version: string|null,
     *     last_updated: string,
     *     sandbox_env_var: string|null,
     *     sandbox_tested_at: string|null,
     *     sandbox_status: string|null,
     *     sandbox_last_error: string|null,
     *     live_tested_at: string|null,
     *     regions: list<string>,
     *     notes: string|null,
     * }
     */
    public function toArray(): array
    {
        return [
            'key' => $this->key,
            'name' => $this->name,
            'composer_package' => $this->composerPackage,
            'sdk_version' => $this->sdkVersion,
            'last_updated' => $this->lastUpdated,
            'sandbox_env_var' => $this->sandboxEnvVar,
            'sandbox_tested_at' => $this->sandboxTestedAt,
            'sandbox_status' => $this->sandboxStatus,
            'sandbox_last_error' => $this->sandboxLastError,
            'live_tested_at' => $this->liveTestedAt,
            'regions' => $this->regions,
            'notes' => $this->notes,
        ];
    }

    public function withSdkVersion(string $sdkVersion, string $lastUpdated): self
    {
        return new self(
            $this->key,
            $this->name,
            $this->composerPackage,
            $sdkVersion,
            $lastUpdated,
            $this->sandboxEnvVar,
            $this->sandboxTestedAt,
            $this->sandboxStatus,
            $this->sandboxLastError,
            $this->liveTestedAt,
            $this->regions,
            $this->notes,
        );
    }

    public function withSandboxResult(string $sandboxTestedAt, string $sandboxStatus, ?string $sandboxLastError): self
    {
        return new self(
            $this->key,
            $this->name,
            $this->composerPackage,
            $this->sdkVersion,
            $this->lastUpdated,
            $this->sandboxEnvVar,
            $sandboxTestedAt,
            $sandboxStatus,
            $sandboxLastError,
            $this->liveTestedAt,
            $this->regions,
            $this->notes,
        );
    }
}
