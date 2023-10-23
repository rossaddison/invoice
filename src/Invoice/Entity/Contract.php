<?php
declare(strict_types=1);

namespace App\Invoice\Entity;

use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Entity;
use Cycle\Annotated\Annotation\Relation\BelongsTo;

use \DateTimeImmutable;

#[Entity(repository: \App\Invoice\Contract\ContractRepository::class)] 
class Contract
{
    #[Column(type: 'primary')]
    public ?int $id = null;
    
    #[BelongsTo(target: \App\Invoice\Entity\Client::class, nullable: false, fkAction: 'NO ACTION')]
    private ?Client $client = null;
    #[Column(type: 'integer(11)', nullable: false)]
    private ?int $client_id =  null;
    
    #[Column(type: 'text', nullable: true)]
    public ?string $reference = null;
    
    #[Column(type: 'text', nullable: true)]
    public ?string $name = null;
    
    #[Column(type: 'datetime', nullable:false)]
    private DateTimeImmutable $period_start;

    #[Column(type: 'datetime', nullable:false)]
    private DateTimeImmutable $period_end;
    
    public function __construct(
        string $name='',
        string $reference='',
        int $client_id=null,
    )
    {
        $this->client_id = $client_id;
        $this->name = $name;
        $this->reference = $reference;
        $this->period_start = new \DateTimeImmutable();
        $this->period_end = new \DateTimeImmutable();
    }
    
    public function getId(): ?int
    {
        return $this->id;
    }
    
    public function getClient_id(): string
    {
        return (string)$this->client_id;
    }
    
    public function setClient_id(int $client_id) : void
    {
      $this->client_id =  $client_id;
    }
    
    public function getClient() : Client|null {
      return $this->client;
    }
    
    public function setClient(?Client $client): void
    {
        $this->client = $client;
    }
     
    public function getName(): string|null
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }
    
    public function getReference(): string|null
    {
        return $this->reference;
    }

    public function setReference(string $reference): void
    {
        $this->reference = $reference;
    }
    
    public function getPeriod_start(): DateTimeImmutable
    {
       return $this->period_start;  
    }
    
    public function setPeriod_start(DateTimeImmutable $period_start) : void
    {
       $this->period_start  = $period_start;  
    }
    
    public function getPeriod_end(): DateTimeImmutable
    {
       return $this->period_end;  
    }
    
    public function setPeriod_end(DateTimeImmutable $period_end) : void
    {
       $this->period_end = $period_end;
    }
    
    public function nullifyRelationOnChange(int $client_id) : void {
       if ($this->client_id <> $client_id) {
           $this->client = null;
       } 
    }
    
    public function isNewRecord(): bool
    {
        return null===$this->getId() ? true : false;
    }
}
