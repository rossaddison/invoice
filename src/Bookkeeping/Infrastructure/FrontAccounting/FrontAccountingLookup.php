<?php

declare(strict_types=1);

namespace App\Bookkeeping\Infrastructure\FrontAccounting;

use App\Bookkeeping\Infrastructure\FrontAccounting\FrontAccountingLookupRepository as FALR;
use App\Infrastructure\Persistence\Trait\RequireId;
use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Entity;
use Cycle\Annotated\Annotation\Table\Index;
use DateTimeImmutable;

/**
 * One cached row from one of FrontAccounting's own small reference lists
 * (tax groups, stock/service items, bank accounts, payment terms), synced
 * by FrontAccountingLookupSyncService via the FrontAccountingSimpleAPI
 * fork's GET /taxgroups/, /inventory/, /bankaccounts, /paymentterms/
 * endpoints. Purely a label cache for the Online Bookkeeping settings
 * tab's FrontAccounting dropdowns -- never read by FrontAccountingGateway
 * itself, which still posts using the raw id stored in the matching
 * `bookkeeping_frontaccounting_*` setting.
 *
 * One table for all four kinds, not four -- same precedent as
 * BookkeepingTransactionLine's own enum-backed $account column.
 *
 * $external_id is a string column, not int: FrontAccounting's own
 * stock_id primary key is a varchar, so an int column can't hold every
 * kind's id uniformly.
 */
#[Entity(repository: FALR::class)]
#[Index(columns: ['kind', 'external_id'], unique: true)]
class FrontAccountingLookup
{
    use RequireId;

    #[Column(type: 'primary')]
    private ?int $id = null;

    public function __construct(
        #[Column(type: 'string(30)', nullable: false)]
        private string $kind,
        #[Column(type: 'string(20)', nullable: false)]
        private string $external_id,
        #[Column(type: 'string(200)', nullable: false)]
        private string $label,
        #[Column(type: 'datetime', nullable: false)]
        private DateTimeImmutable $synced_at,
    ) {
    }

    public function reqId(): int
    {
        return $this->requireId($this->id, 'FrontAccountingLookup');
    }

    public function isPersisted(): bool
    {
        return $this->id !== null;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function getKind(): FrontAccountingLookupKind
    {
        return FrontAccountingLookupKind::from($this->kind);
    }

    public function setKind(FrontAccountingLookupKind $kind): void
    {
        $this->kind = $kind->value;
    }

    public function getExternalId(): string
    {
        return $this->external_id;
    }

    public function setExternalId(string $external_id): void
    {
        $this->external_id = $external_id;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function setLabel(string $label): void
    {
        $this->label = $label;
    }

    public function getSyncedAt(): DateTimeImmutable
    {
        return $this->synced_at;
    }

    public function setSyncedAt(DateTimeImmutable $synced_at): void
    {
        $this->synced_at = $synced_at;
    }
}
