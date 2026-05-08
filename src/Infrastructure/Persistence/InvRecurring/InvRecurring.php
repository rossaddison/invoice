<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\InvRecurring;

use App\Infrastructure\Persistence\{
    Inv\Inv, Trait\RequireId
};
use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Entity;
use Cycle\Annotated\Annotation\Relation\BelongsTo;
use DateTime;
use DateTimeImmutable;

#[Entity(repository: \App\Invoice\InvRecurring\InvRecurringRepository::class)]

class InvRecurring
{
    use RequireId;
 
    /**
     * Every Recurring Invoice record belongs to one related Invoice
     * @var Inv $inv
     */
    #[BelongsTo(target: Inv::class, nullable: false, fkAction: 'NO ACTION')]
    private ?Inv $inv = null;

    public function __construct(
        #[Column(type: 'primary')]
        private ?int $id = null,
        #[Column(type: 'integer(11)', nullable: false)]
        private ?int $inv_id = null,
        #[Column(type: 'date', nullable: false)]
        private mixed $start = '',
        #[Column(type: 'date', nullable: true)]
        private mixed $end = '',
        #[Column(type: 'string(191)', nullable: false)]
        private string $frequency = '',
        #[Column(type: 'date', nullable: true)]
        private mixed $next = '')
    {
    }

    public function reqId(): int
    {
        return $this->requireId($this->id, 'InvCustom');
    }
    
    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function getInv(): ?Inv
    {
        return $this->inv;
    }

    public function setInv(?Inv $inv): void
    {
        $this->inv = $inv;
    }

    public function reqInvId(): int
    {
        return $this->requireId($this->inv_id, 'Inv');
    }
    
    public function setInvId(int $inv_id): void
    {
        $this->inv_id = $inv_id;
    }

    public function getStart(): string|DateTimeImmutable
    {
        /** @var DateTimeImmutable|string $this->start */
        return $this->start;
    }

    public function setStart(string|DateTime $start): void
    {
        $this->start = $start;
    }

    public function getEnd(): string|DateTimeImmutable|null
    {
        /** @var DateTimeImmutable|string|null $this->end */
        return $this->end;
    }

    public function setEnd(?DateTime $end): void
    {
        $this->end = $end;
    }

    public function getNext(): string|DateTimeImmutable|null
    {
        /** @var DateTimeImmutable|string|null $this->next */
        return $this->next;
    }

    public function setNext(?DateTime $next): void
    {
        $this->next = $next;
    }

    public function getFrequency(): string
    {
        return $this->frequency;
    }

    public function setFrequency(string $frequency): void
    {
        $this->frequency = $frequency;
    }
}
