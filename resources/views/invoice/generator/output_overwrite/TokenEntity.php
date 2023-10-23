<?php

declare(strict_types=1); 

namespace App\Invoice\Entity;

use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Entity;
use Cycle\Annotated\Annotation\Relation\BelongsTo;
use DateTime;
use DateTimeImmutable;use App\Invoice\Entity\Identity;
use App\Invoice\Entity\Identity;
  
 #[Entity(repository: \App\Invoice\Token\TokenRepository::class)]
 
 class Token
 {
       
        #[BelongsTo(target:Identity::class, nullable: false, fkAction:'NO ACTION')]
     private ?Identity $identity = null;
    
     #[BelongsTo(target:Identity::class, nullable: false, fkAction:'NO ACTION')]
     private ?Identity $identity = null;
    
    
         #[Column(type:'primary')]
     private ?int $id =  null;
     
     #[Column(type:'integer(11)', nullable: false)]
     private ?int $identity_id =  null;
     
     #[Column(type:'integer(11)', nullable: false)]
     private ?int $code =  null;
     
     #[Column(type:'datetime)', nullable: false)]
     private DateTimeImmutable $created_at;
     
     public function __construct(
          int $id = null,
         int $identity_id = null,
         int $code = null,
          $created_at = ''
     )
     {
         $this->id=$id;
         $this->identity_id=$identity_id;
         $this->code=$code;
         $this->created_at=$created_at;
     }
    
    public function getIdentity() : ?Identity
    {
      return $this->identity;
    }
    
    public function setIdentity(?Identity $identity): void
    {
      $this->identity = $identity;
    }
    
    public function getIdentity() : ?Identity
    {
      return $this->identity;
    }
    
    public function setIdentity(?Identity $identity): void
    {
      $this->identity = $identity;
    }
    
    public function getId(): string
    {
     return (string)$this->id;
    }
    
    public function setId(int $id) : void
    {
      $this->id =  $id;
    }
    
    public function getIdentity_id(): string
    {
     return (string)$this->identity_id;
    }
    
    public function setIdentity_id(int $identity_id) : void
    {
      $this->identity_id =  $identity_id;
    }
    
    public function getCode(): int
    {
       return $this->code;
    }
    
    public function setCode(int $code) : void
    {
      $this->code =  $code;
    }
    
    public function getCreated_at(): string
    {
       return $this->created_at;
    }
    
    public function setCreated_at(string $created_at) : void
    {
      $this->created_at =  $created_at;
    }

    public function nullifyRelationOnChange(int $identity_id, int $identity_id) : void 
    {
            if ($this->identity_id <> $identity_id) {
            $this->identity = null;
        }
if ($this->identity_id <> $identity_id) {
            $this->identity = null;
        }
    }
}