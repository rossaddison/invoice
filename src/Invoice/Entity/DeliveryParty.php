<?php
declare(strict_types=1); 

namespace App\Invoice\Entity;

use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Entity;

#[Entity(repository: \App\Invoice\DeliveryParty\DeliveryPartyRepository::class)]

class DeliveryParty
{
    #[Column(type: 'primary')]
    private ?int $id =  null;
     
    #[Column(type: 'text', nullable: true)]
    private ?string $party_name =  '';
     
    public function __construct(
       int $id = null,
       string $party_name = ''
    )
    {
       $this->id=$id;
       $this->party_name=$party_name;
    }
    
    public function getId(): int|null
    {
       return $this->id;
    }
    
    public function setId(int $id) : void
    {
       $this->id =  $id;
    }
    
    public function getPartyName(): string|null
    {
       return $this->party_name;
    }
    
    public function setPartyName(string $party_name) : void
    {
       $this->party_name =  $party_name;
    }
    
    public function isNewRecord(): bool
    {
       return $this->getId() === null;
    }
}