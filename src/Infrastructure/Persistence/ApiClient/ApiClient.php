<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\ApiClient;

use App\Api\ApiClientRepository;
use App\Infrastructure\Persistence\Trait\RequireId;
use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Entity;
use Cycle\Annotated\Annotation\Table\Index;
use Cycle\ORM\Entity\Behavior;
use DateTimeImmutable;

/**
 * One row per external caller allowed through `ApiKeyAuthMiddleware` — e.g.
 * the future headless webshop storefront (see
 * `docs/WEBSHOP_HEADLESS_STOREFRONT_DESIGN_AUGUST_2026.md`). This is a
 * separate auth mechanism from the RBAC session cookie every other `/api`
 * route relies on via `RoutePermission::check()`, which a
 * separately-deployed external app has no way to hold.
 *
 * `key_hash` is the sha256 hex digest of the plaintext key handed out once
 * at generation time (`Console\GenerateApiKeyCommand`) — the plaintext
 * itself is never stored, so a leaked database backup alone doesn't expose
 * a usable key. Revocation is `enabled = false`, not row deletion, so
 * `last_used_at` history survives for auditing.
 */
#[Entity(repository: ApiClientRepository::class)]
#[Index(columns: ['key_hash'], name: 'api_client_key_hash_idx', unique: true)]
#[Behavior\CreatedAt(field: 'date_created', column: 'date_created')]
class ApiClient
{
    use RequireId;

    #[Column(type: 'primary')]
    private ?int $id = null;

    #[Column(type: 'datetime')]
    private DateTimeImmutable $date_created;

    public function __construct(
        #[Column(type: 'string(100)', nullable: false)]
        private string $name = '',
        #[Column(type: 'string(64)', nullable: false)]
        private string $key_hash = '',
        #[Column(type: 'boolean', nullable: false, default: true)]
        private bool $enabled = true,
        #[Column(type: 'datetime', nullable: true)]
        private ?DateTimeImmutable $last_used_at = null,
    ) {
        $this->date_created = new DateTimeImmutable();
    }

    public function reqId(): int
    {
        return $this->requireId($this->id, 'ApiClient');
    }

    public function isPersisted(): bool
    {
        return $this->id !== null;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getKeyHash(): string
    {
        return $this->key_hash;
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function setEnabled(bool $enabled): void
    {
        $this->enabled = $enabled;
    }

    public function getLastUsedAt(): ?DateTimeImmutable
    {
        return $this->last_used_at;
    }

    public function touchLastUsed(): void
    {
        $this->last_used_at = new DateTimeImmutable();
    }

    public function getDateCreated(): DateTimeImmutable
    {
        return $this->date_created;
    }
}
