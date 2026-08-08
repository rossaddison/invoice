<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\GatewayStatus;

use App\Infrastructure\Persistence\Trait\RequireId;
use App\Invoice\PaymentInformation\GatewayStatus\GatewayStatusRepository;
use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Entity;
use Cycle\Annotated\Annotation\Table\Index;

/**
 * One row per payment gateway this app supports, shown on the public
 * `site/gateway-status` page. Lives in its own SQLite database (`database:
 * 'gateway_status'` below) rather than the main MySQL one, and is deliberately
 * decoupled from `gateways.json` — the JSON file is what a maintainer/CI job
 * actually edits (see docs/GATEWAY_STATUS_PAGE_AUGUST_2026.md); this table is
 * a synced projection of it, rebuilt by
 * App\Invoice\PaymentInformation\GatewayStatus\GatewayStatusService.
 *
 * `sandbox_last_error` is intentionally never rendered on the public page —
 * it exists purely for maintainer diagnostics after a failed automated
 * sandbox check.
 *
 * `expiry_date` is human-entered (via gateways.json, never written by
 * either console command) — when this gateway's *sandbox* credential is
 * known to expire. CheckGatewaySandboxesCommand sends a Telegram alert
 * once it's passed; see that command's own docblock.
 */
#[Entity(repository: GatewayStatusRepository::class, database: 'gateway_status')]
#[Index(columns: ['gateway_key'], unique: true)]
#[Index(columns: ['last_updated'], name: 'gateway_status_last_updated_idx')]
#[Index(columns: ['sandbox_tested_at'], name: 'gateway_status_sandbox_tested_at_idx')]
#[Index(columns: ['live_tested_at'], name: 'gateway_status_live_tested_at_idx')]
#[Index(columns: ['expiry_date'], name: 'gateway_status_expiry_date_idx')]
class GatewayStatus
{
    use RequireId;

    #[Column(type: 'primary')]
    private ?int $id = null;

    public function __construct(
        #[Column(type: 'string(50)', nullable: false)]
        private string $gateway_key = '',
        #[Column(type: 'string(100)', nullable: false)]
        private string $name = '',
        #[Column(type: 'string(50)', nullable: true)]
        private ?string $sdk_version = null,
        #[Column(type: 'string(10)', nullable: false)]
        private string $last_updated = '',
        #[Column(type: 'string(10)', nullable: true)]
        private ?string $sandbox_tested_at = null,
        #[Column(type: 'string(20)', nullable: true)]
        private ?string $sandbox_status = null,
        #[Column(type: 'text', nullable: true)]
        private ?string $sandbox_last_error = null,
        #[Column(type: 'string(10)', nullable: true)]
        private ?string $live_tested_at = null,
        #[Column(type: 'string(10)', nullable: true)]
        private ?string $expiry_date = null,
        #[Column(type: 'string(255)', nullable: false)]
        private string $regions = '',
        #[Column(type: 'text', nullable: true)]
        private ?string $notes = null,
    ) {
    }

    public function reqId(): int
    {
        return $this->requireId($this->id, 'GatewayStatus');
    }

    public function isPersisted(): bool
    {
        return $this->id !== null;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function getGatewayKey(): string
    {
        return $this->gateway_key;
    }

    public function setGatewayKey(string $gateway_key): void
    {
        $this->gateway_key = $gateway_key;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getSdkVersion(): ?string
    {
        return $this->sdk_version;
    }

    public function setSdkVersion(?string $sdk_version): void
    {
        $this->sdk_version = $sdk_version;
    }

    public function getLastUpdated(): string
    {
        return $this->last_updated;
    }

    public function setLastUpdated(string $last_updated): void
    {
        $this->last_updated = $last_updated;
    }

    public function getSandboxTestedAt(): ?string
    {
        return $this->sandbox_tested_at;
    }

    public function setSandboxTestedAt(?string $sandbox_tested_at): void
    {
        $this->sandbox_tested_at = $sandbox_tested_at;
    }

    public function getSandboxStatus(): ?string
    {
        return $this->sandbox_status;
    }

    public function setSandboxStatus(?string $sandbox_status): void
    {
        $this->sandbox_status = $sandbox_status;
    }

    public function getSandboxLastError(): ?string
    {
        return $this->sandbox_last_error;
    }

    public function setSandboxLastError(?string $sandbox_last_error): void
    {
        $this->sandbox_last_error = $sandbox_last_error;
    }

    public function getLiveTestedAt(): ?string
    {
        return $this->live_tested_at;
    }

    public function setLiveTestedAt(?string $live_tested_at): void
    {
        $this->live_tested_at = $live_tested_at;
    }

    public function getExpiryDate(): ?string
    {
        return $this->expiry_date;
    }

    public function setExpiryDate(?string $expiry_date): void
    {
        $this->expiry_date = $expiry_date;
    }

    /**
     * Comma-joined continent slugs, e.g. "europe,asia".
     */
    public function getRegions(): string
    {
        return $this->regions;
    }

    public function setRegions(string $regions): void
    {
        $this->regions = $regions;
    }

    /**
     * @return list<string>
     */
    public function getRegionsList(): array
    {
        return $this->regions === '' ? [] : explode(',', $this->regions);
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function setNotes(?string $notes): void
    {
        $this->notes = $notes;
    }
}
