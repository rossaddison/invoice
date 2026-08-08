<?php

declare(strict_types=1);

namespace App\Invoice\PaymentInformation\GatewayStatus;

/**
 * One entry from resources/gateway-status/gateways.json. Field ownership
 * (see docs/GATEWAY_STATUS_PAGE_AUGUST_2026.md): $sdkVersion/$lastUpdated
 * are rewritten only by RebuildGatewayStatusCommand (from composer.lock);
 * $sandboxTestedAt/$sandboxStatus/$sandboxLastError only by
 * CheckGatewaySandboxesCommand; everything else — including $expiryDate —
 * is human-curated and must never be touched by either command.
 */
final readonly class GatewayStatusRow
{
    /**
     * @param list<string> $regions
     * @param list<string> $sandboxEnvVars Names of the GitHub Actions repo
     *   secrets/environment variables CheckGatewaySandboxesCommand reads for
     *   this gateway's sandbox check, in the order its own checkGateway()
     *   case expects them. Empty means no sandbox check is wired up yet.
     *   Most gateways need exactly one (an API key/access token); Adyen
     *   needs two (API key + merchant account — paymentMethods() requires
     *   both, see docs/GATEWAY_STATUS_PAGE_AUGUST_2026.md).
     * @param string|null $expiryDate `Y-m-d`, human-entered — when this
     *   gateway's *sandbox* credential/account is known to expire (e.g. a
     *   time-limited test merchant account or API key). Never set
     *   automatically; left null unless you actually know an expiry date.
     *   CheckGatewaySandboxesCommand sends a Telegram alert when this date
     *   has passed — see its own docblock and
     *   docs/GATEWAY_STATUS_PAGE_AUGUST_2026.md. Deliberately scoped to
     *   sandbox credentials only, not this app's own encrypted production
     *   gateway credentials (Setting table) — a separate trust boundary.
     */
    public function __construct(
        public string $key,
        public string $name,
        public ?string $composerPackage,
        public ?string $sdkVersion,
        public string $lastUpdated,
        public array $sandboxEnvVars,
        public ?string $sandboxTestedAt,
        public ?string $sandboxStatus,
        public ?string $sandboxLastError,
        public ?string $liveTestedAt,
        public ?string $expiryDate,
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
     *     sandbox_env_var?: string|list<string>|null,
     *     sandbox_tested_at?: string|null,
     *     sandbox_status?: string|null,
     *     sandbox_last_error?: string|null,
     *     live_tested_at?: string|null,
     *     expiry_date?: string|null,
     *     regions?: array<array-key, string>,
     *     notes?: string|null,
     * } $data
     */
    public static function fromArray(array $data): self
    {
        $regions = array_values($data['regions'] ?? []);
        $rawEnvVar = $data['sandbox_env_var'] ?? null;
        $sandboxEnvVars = match (true) {
            $rawEnvVar === null => [],
            is_array($rawEnvVar) => $rawEnvVar,
            default => [$rawEnvVar],
        };

        return new self(
            key: $data['key'] ?? '',
            name: $data['name'] ?? '',
            composerPackage: $data['composer_package'] ?? null,
            sdkVersion: $data['sdk_version'] ?? null,
            lastUpdated: $data['last_updated'] ?? '',
            sandboxEnvVars: $sandboxEnvVars,
            sandboxTestedAt: $data['sandbox_tested_at'] ?? null,
            sandboxStatus: $data['sandbox_status'] ?? null,
            sandboxLastError: $data['sandbox_last_error'] ?? null,
            liveTestedAt: $data['live_tested_at'] ?? null,
            expiryDate: $data['expiry_date'] ?? null,
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
     *     sandbox_env_var: string|list<string>|null,
     *     sandbox_tested_at: string|null,
     *     sandbox_status: string|null,
     *     sandbox_last_error: string|null,
     *     live_tested_at: string|null,
     *     expiry_date: string|null,
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
            // A single-var gateway keeps the plain-string JSON shape every
            // existing entry already has (readable, no needless diff); only
            // a genuinely multi-value gateway (Adyen) serializes as an array.
            'sandbox_env_var' => match (count($this->sandboxEnvVars)) {
                0 => null,
                1 => $this->sandboxEnvVars[0],
                default => $this->sandboxEnvVars,
            },
            'sandbox_tested_at' => $this->sandboxTestedAt,
            'sandbox_status' => $this->sandboxStatus,
            'sandbox_last_error' => $this->sandboxLastError,
            'live_tested_at' => $this->liveTestedAt,
            'expiry_date' => $this->expiryDate,
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
            $this->sandboxEnvVars,
            $this->sandboxTestedAt,
            $this->sandboxStatus,
            $this->sandboxLastError,
            $this->liveTestedAt,
            $this->expiryDate,
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
            $this->sandboxEnvVars,
            $sandboxTestedAt,
            $sandboxStatus,
            $sandboxLastError,
            $this->liveTestedAt,
            $this->expiryDate,
            $this->regions,
            $this->notes,
        );
    }

    /**
     * True once today (`Y-m-d`) is on or after $expiryDate. False when no
     * expiry date is set at all.
     */
    public function isExpired(string $today): bool
    {
        return $this->expiryDate !== null && $this->expiryDate <= $today;
    }
}
