<?php

declare(strict_types=1);

namespace App\Bookkeeping\Infrastructure\Persistence;

use App\Bookkeeping\Domain\AccountRole;
use App\Bookkeeping\Domain\DebitCredit;
use App\Bookkeeping\Infrastructure\Persistence\BookkeepingTransactionLineRepository as BTLR;
use App\Infrastructure\Persistence\Trait\RequireId;
use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Entity;
use Cycle\Annotated\Annotation\Relation\BelongsTo;
use Cycle\Annotated\Annotation\Table\Index;

/**
 * One debit or credit line within a persisted BookkeepingTransaction --
 * its own table (BelongsTo), not a JSON column on the parent, matching
 * this app's existing parent/child convention (Inv/InvItem, Quote/
 * QuoteItem) rather than inventing a new one for this module.
 * Cycle-attributed counterpart of App\Bookkeeping\Domain\BookkeepingLine;
 * CycleBookkeepingTransactionRepository maps between the two.
 *
 * $account and $direction are stored as plain string columns and re-typed
 * to their enums on the way out -- same reasoning as BookkeepingTransaction's
 * own $type column.
 */
#[Entity(repository: BTLR::class)]
#[Index(columns: ['bookkeeping_transaction_id'])]
class BookkeepingTransactionLine
{
    use RequireId;

    #[Column(type: 'primary')]
    private ?int $id = null;

    #[BelongsTo(target: BookkeepingTransaction::class, nullable: false, fkAction: 'CASCADE')]
    private ?BookkeepingTransaction $bookkeeping_transaction = null;

    public function __construct(
        #[Column(type: 'integer(11)', nullable: false)]
        private ?int $bookkeeping_transaction_id = null,
        #[Column(type: 'string(30)', nullable: false)]
        private string $account = AccountRole::Bank->value,
        #[Column(type: 'string(10)', nullable: false)]
        private string $direction = DebitCredit::Debit->value,
        #[Column(type: 'decimal(20,2)', nullable: false, default: 0.00)]
        private float $amount = 0.00,
        #[Column(type: 'string(50)', nullable: true)]
        private ?string $tax_code = null,
        #[Column(type: 'string(500)', nullable: true)]
        private ?string $description = null,
    ) {
    }

    public function reqId(): int
    {
        return $this->requireId($this->id, 'BookkeepingTransactionLine');
    }

    public function isPersisted(): bool
    {
        return $this->id !== null;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function getBookkeepingTransaction(): ?BookkeepingTransaction
    {
        return $this->bookkeeping_transaction;
    }

    public function setBookkeepingTransaction(?BookkeepingTransaction $bookkeeping_transaction): void
    {
        $this->bookkeeping_transaction = $bookkeeping_transaction;
    }

    public function reqBookkeepingTransactionId(): int
    {
        return $this->requireId($this->bookkeeping_transaction_id, 'BookkeepingTransaction');
    }

    public function setBookkeepingTransactionId(int $bookkeeping_transaction_id): void
    {
        $this->bookkeeping_transaction_id = $bookkeeping_transaction_id;
    }

    public function getAccount(): AccountRole
    {
        return AccountRole::from($this->account);
    }

    public function setAccount(AccountRole $account): void
    {
        $this->account = $account->value;
    }

    public function getDirection(): DebitCredit
    {
        return DebitCredit::from($this->direction);
    }

    public function setDirection(DebitCredit $direction): void
    {
        $this->direction = $direction->value;
    }

    public function getAmount(): float
    {
        return $this->amount;
    }

    public function setAmount(float $amount): void
    {
        $this->amount = $amount;
    }

    public function getTaxCode(): ?string
    {
        return $this->tax_code;
    }

    public function setTaxCode(?string $tax_code): void
    {
        $this->tax_code = $tax_code;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): void
    {
        $this->description = $description;
    }
}
