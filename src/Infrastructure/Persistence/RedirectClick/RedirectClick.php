<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\RedirectClick;

use App\Infrastructure\Persistence\Trait\RequireId;
use App\Redirect\RedirectClickRepository;
use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Entity;
use Cycle\Annotated\Annotation\Table\Index;
use Cycle\ORM\Entity\Behavior;
use DateTimeImmutable;

/**
 * One row per click through a tracked outbound link (`GET /go/{key}`,
 * `RedirectController::go()`) — e.g. the homepage's "View the source on
 * GitHub" button. `country_code` is a best-effort lowercase ISO 3166-1
 * alpha-2 code (matching the casing `flekschas/simple-world-map`'s own
 * SVG path IDs use — see `resources/views/redirect/map.php`), resolved
 * via `GeoIpLookupService`; null when the lookup failed or was skipped
 * (e.g. a private/loopback IP during local development) — the redirect
 * itself always succeeds regardless, this table is purely observational.
 *
 * Deliberately does not store the visitor's IP address at all, only the
 * country it resolved to — the minimum needed for the choropleth map,
 * not a general analytics/tracking table.
 */
#[Entity(repository: RedirectClickRepository::class)]
#[Index(columns: ['link_key'], name: 'redirect_click_link_key_idx')]
#[Index(columns: ['country_code'], name: 'redirect_click_country_code_idx')]
#[Behavior\CreatedAt(field: 'date_created', column: 'date_created')]
class RedirectClick
{
    use RequireId;

    #[Column(type: 'primary')]
    private ?int $id = null;

    #[Column(type: 'datetime')]
    private DateTimeImmutable $date_created;

    public function __construct(
        #[Column(type: 'string(50)', nullable: false)]
        private string $link_key = '',
        #[Column(type: 'string(2)', nullable: true)]
        private ?string $country_code = null,
    ) {
        $this->date_created = new DateTimeImmutable();
    }

    public function reqId(): int
    {
        return $this->requireId($this->id, 'RedirectClick');
    }

    public function isPersisted(): bool
    {
        return $this->id !== null;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function getLinkKey(): string
    {
        return $this->link_key;
    }

    public function setLinkKey(string $link_key): void
    {
        $this->link_key = $link_key;
    }

    public function getCountryCode(): ?string
    {
        return $this->country_code;
    }

    public function setCountryCode(?string $country_code): void
    {
        $this->country_code = $country_code;
    }

    public function getDateCreated(): DateTimeImmutable
    {
        return $this->date_created;
    }
}
