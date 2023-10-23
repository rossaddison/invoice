<?php
declare(strict_types=1); 

namespace App\Invoice\Entity;

use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Entity;
use Cycle\Annotated\Annotation\Relation\BelongsTo;
use \DateTimeImmutable;  
#[Entity(repository: \App\Invoice\InvRecurring\InvRecurringRepository::class)]
 
class InvRecurring
{   
    #[Column(type:'primary')]
    private ?int $id =  null;

    #[BelongsTo(target:\App\Invoice\Entity\Inv::class, nullable: false, fkAction: 'NO ACTION')]
    private ?Inv $inv = null;
    
    #[Column(type:'integer(11)', nullable: false)]
    private ?int $inv_id =  null;

    #[Column(type:'date', nullable: true)]
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
        string $frequency = '',
    )
    {
        $this->id=$id;
        $this->inv_id=$inv_id;
        $this->frequency=$frequency;
    }

    public function getId(): string
    {
        return (string)$this->id;
    }

    public function setId(int $id) : void
    {
        $this->id =  $id;
    }
    
    public function getInv(): Inv|null
    {
        return $this->inv;
    }

    public function getInv_id(): string
    {
        return (string)$this->inv_id;
    }

    public function setInv_id(int $inv_id) : void
    {
        $this->inv_id =  $inv_id;
    }
    
    public function getStart() : DateTimeImmutable  
    {
        /** @var DateTimeImmutable $this->start */
        return $this->start;
    }    
    
    public function setStart(string $start): void
    {
        $this->start = new \DateTime($start);
    }
    
    public function getEnd() : DateTimeImmutable  
    {
        /** @var DateTimeImmutable $this->end */
        return $this->end;
    }    
    
    public function setEnd(string $end): void
    {
        $this->end = new \DateTime($end);
    }
    
    public function getNext() : DateTimeImmutable|null  
    {
        /** @var DateTimeImmutable $this->next */
        return $this->next;
    }    
    
    public function setNext(string $next): void 
    {
        $this->next = new \DateTime($next);
    }

    public function getFrequency(): string
    {
        return $this->frequency;
    }

    public function setFrequency(string $frequency) : void
    {
        $this->frequency =  $frequency;
    }
}