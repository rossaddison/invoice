<?php

declare(strict_types=1);

namespace App\Invoice\Entity;

use App\Invoice\Entity\Inv;
use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Entity;
use Cycle\Annotated\Annotation\Relation\BelongsTo;
use DateTime;
use DateTimeImmutable;

#[Entity(repository: \App\Invoice\InvRecurring\InvRecurringRepository::class)]

class InvRecurring
{
    #[Column(type:'primary')]
    private ?int $id =  null;

    /**
     * Every Recurring Invoice record belongs to one related Invoice
     * @var Inv $inv
     */
    #[BelongsTo(target:Inv::class, nullable: false, fkAction: "NO ACTION")]
    private ?Inv $inv = null;

    #[Column(type:'integer(11)', nullable: false)]
    private ?int $inv_id =  null;

    #[Column(type:'date', nullable: false)]
    private mixed $start;

    #[Column(type:'date', nullable: true)]
    private mixed $end;

    #[Column(type:'string(191)', nullable: false)]
    private string $frequency =  '';

    #[Column(type:'date', nullable: true)]
    private mixed $next;

    public function __construct(
        int $id = null,
        int $inv_id = null,
        mixed $start = '',
        mixed $end = '',
        string $frequency = '',
        mixed $next = '',
    ) {
        $this->id = $id;
        $this->inv_id = $inv_id;
        $this->start = $start;
        $this->end = $end;
        $this->frequency = $frequency;
        $this->next = $next;
    }

    public function getId(): string
    {
        return (string)$this->id;
    }

    public function setId(int $id): void
    {
        $this->id =  $id;
    }

    public function getInv(): ?Inv
    {
        return $this->inv;
    }

    public function getInv_id(): string
    {
        return (string)$this->inv_id;
    }

    public function setInv_id(int $inv_id): void
    {
        $this->inv_id =  $inv_id;
    }

    public function getStart(): string|DateTimeImmutable
    {
        /** @var string|DateTimeImmutable $this->start */
        return $this->start;
    }

    public function setStart(string|DateTime $start): void
    {
        $this->start = $start;
    }

    public function getEnd(): string|null|DateTimeImmutable
    {
        /** @var string|null|DateTimeImmutable $this->end */
        return $this->end;
    }

    public function setEnd(?DateTime $end): void
    {
        $this->end = $end;
    }

    public function getNext(): string|null|DateTimeImmutable
    {
        /** @var string|null|DateTimeImmutable $this->next */
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
        $this->frequency =  $frequency;
    }
}
