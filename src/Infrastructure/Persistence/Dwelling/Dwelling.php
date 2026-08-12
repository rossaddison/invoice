<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Dwelling;

use App\Infrastructure\Persistence\Trait\RequireId;
use App\Invoice\Dwelling\DwellingRepository;
use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Entity;
use Cycle\Annotated\Annotation\Table\Index;
use Cycle\ORM\Entity\Behavior;

/**
 * A single addressable house/unit on a HomeCare street ({@see \App\Infrastructure\Persistence\Family\Family}).
 * Replaces the earlier repurposing of {@see \App\Infrastructure\Persistence\Product\Product} to represent
 * a house number — Product goes back to meaning only "sellable catalog item".
 *
 * `family_id` is a plain scalar FK, deliberately not a Cycle `#[BelongsTo]` relation, matching the working
 * precedent already in this codebase: {@see \App\Infrastructure\Persistence\Client\Client::$postaladdress_id}
 * resolved manually via `PostalAddressRepository::repoClient()`. This sidesteps a confirmed real gotcha
 * live in {@see \App\Infrastructure\Persistence\Product\Product}, whose `family_id` column is
 * `nullable: true` while its `BelongsTo` relation annotation says `nullable: false` — a live inconsistency.
 * Nullable at the schema level only to avoid that gotcha; in practice always set, enforced via a
 * `Required` rule on {@see \App\Invoice\Dwelling\DwellingForm}, not by the DB column.
 *
 * "Claimed" status (does a Client currently live here) is deliberately not a stored flag on this entity —
 * it is always determined live via an anti-join against
 * {@see \App\Infrastructure\Persistence\Client\Client::$dwelling_id}, since a denormalized flag has no
 * structural guarantee of staying in sync with the real relationship.
 */
#[Entity(repository: DwellingRepository::class)]
#[Behavior\CreatedAt(field: 'date_created', column: 'date_created')]
#[Behavior\UpdatedAt(field: 'date_modified', column: 'date_modified')]
#[Index(columns: ['family_id', 'house_number_numeric', 'house_number_suffix'])]
#[Index(columns: ['postcode'])]
class Dwelling
{
    use RequireId;

    #[Column(type: 'primary')]
    private ?int $id = null;

    #[Column(type: 'datetime')]
    private \DateTimeImmutable $date_created;

    #[Column(type: 'datetime')]
    private \DateTimeImmutable $date_modified;

    public function __construct(
        #[Column(type: 'integer(11)', nullable: true)]
        private ?int $family_id = null,
        /**
         * Split from any letter suffix specifically so `ORDER BY` sorts "12" before "12A" before "13"
         * correctly — a single string column can't do that without a natural-sort library this codebase
         * doesn't have.
         */
        #[Column(type: 'integer(11)', nullable: false, default: 0)]
        private int $house_number_numeric = 0,
        #[Column(type: 'string(5)', nullable: true)]
        private ?string $house_number_suffix = null,
        /**
         * Royal Mail PAF's sub-building designator — "Flat 2", shown before the house number when present.
         */
        #[Column(type: 'string(50)', nullable: true)]
        private ?string $flat_unit = null,
        /**
         * Normalized uppercase, no internal space (e.g. "SW1A1AA"). The inward code is always the last 3
         * characters of a valid UK postcode, so outward/inward split-out is trivial on demand — no need
         * for two columns. Format validation belongs in DwellingForm, not a DB constraint.
         */
        #[Column(type: 'string(10)', nullable: false, default: '')]
        private string $postcode = '',
        /**
         * Caches whatever geolocation eventually resolves this dwelling to (GPS from a worker's doorstep
         * signup, or a postcodes.io centroid lookup) — avoids re-resolving coordinates on every geofencing
         * check or Street View render.
         */
        #[Column(type: 'float', nullable: true)]
        private ?float $latitude = null,
        #[Column(type: 'float', nullable: true)]
        private ?float $longitude = null,
        /**
         * Which creation path produced this row — 'commalist', 'spreadsheet_import', 'worker_canvassing',
         * 'signup_freehand'. Useful for tracing data-quality issues back to their origin given how many
         * different paths create Dwelling rows.
         */
        #[Column(type: 'string(30)', nullable: true)]
        private ?string $source = null,
    ) {
        $this->date_created = new \DateTimeImmutable();
        $this->date_modified = new \DateTimeImmutable();
    }

    public function reqId(): int
    {
        return $this->requireId($this->id, 'Dwelling');
    }

    public function hasIdentity(): bool
    {
        return $this->id !== null;
    }

    public function isPersisted(): bool
    {
        return $this->id !== null;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function getDateCreated(): \DateTimeImmutable
    {
        return $this->date_created;
    }

    public function getDateModified(): \DateTimeImmutable
    {
        return $this->date_modified;
    }

    public function getFamilyId(): ?int
    {
        return $this->family_id;
    }

    public function reqFamilyId(): int
    {
        return $this->requireId($this->family_id, 'Family');
    }

    public function setFamilyId(int $family_id): void
    {
        $this->family_id = $family_id;
    }

    public function getHouseNumberNumeric(): int
    {
        return $this->house_number_numeric;
    }

    public function setHouseNumberNumeric(int $house_number_numeric): void
    {
        $this->house_number_numeric = $house_number_numeric;
    }

    public function getHouseNumberSuffix(): ?string
    {
        return $this->house_number_suffix;
    }

    public function setHouseNumberSuffix(?string $house_number_suffix): void
    {
        $this->house_number_suffix = $house_number_suffix;
    }

    /**
     * House number and suffix combined for display, e.g. "12A". Never includes the flat/unit — see
     * {@see self::getFlatUnit()} for that, which conventionally comes before this in a formatted address.
     */
    public function getHouseNumberDisplay(): string
    {
        return $this->house_number_numeric . ($this->house_number_suffix ?? '');
    }

    public function getFlatUnit(): ?string
    {
        return $this->flat_unit;
    }

    public function setFlatUnit(?string $flat_unit): void
    {
        $this->flat_unit = $flat_unit;
    }

    public function getPostcode(): string
    {
        return $this->postcode;
    }

    public function setPostcode(string $postcode): void
    {
        $this->postcode = strtoupper(str_replace(' ', '', $postcode));
    }

    public function getLatitude(): ?float
    {
        return $this->latitude;
    }

    public function setLatitude(?float $latitude): void
    {
        $this->latitude = $latitude;
    }

    public function getLongitude(): ?float
    {
        return $this->longitude;
    }

    public function setLongitude(?float $longitude): void
    {
        $this->longitude = $longitude;
    }

    public function getSource(): ?string
    {
        return $this->source;
    }

    public function setSource(?string $source): void
    {
        $this->source = $source;
    }
}
