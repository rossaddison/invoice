<?php

declare(strict_types=1); 

namespace App\Invoice\Entity;

use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Entity;
use Cycle\Annotated\Annotation\Relation\BelongsTo;
use App\Invoice\Entity\Unit;
  
 #[Entity(repository: \App\Invoice\UnitPeppol\UnitPeppolRepository::class)]
 
 class UnitPeppol
 { 
     #[BelongsTo(target:Unit::class, nullable: false, fkAction:'NO ACTION')]
     private ?Unit $unit = null;
     
     #[Column(type: 'primary')]
     private ?int $id =  null;
     
     #[Column(type:'integer(11)', nullable: false)]
     private ?int $unit_id =  null;
     
     #[Column(type:'string(3)', nullable: false)]
     private string $code =  '';
     
     #[Column(type:'string(120)', nullable: false)]
     private string $name =  '';
     
     #[Column(type:'longText', nullable: false)]
     private string $description =  '';
          
     public function __construct(
         int $id = null,
         int $unit_id = null,
         string $code = '',
         string $name = '',
         string $description = '',
     )
     {
         $this->id=$id;
         $this->unit_id=$unit_id;
         $this->code=$code;
         $this->name=$name;
         $this->description=$description;
     }
    
    public function getUnit() : ?Unit
    {
      return $this->unit;
    }
    
    public function setUnit(?Unit $unit): void
    {
      $this->unit = $unit;
    }
    
    public function getId(): string
    {
     return (string)$this->id;
    }
    
    public function setId(int $id) : void
    {
      $this->id =  $id;
    }
    
    public function getUnit_id(): string
    {
     return (string)$this->unit_id;
    }
    
    public function setUnit_id(int $unit_id) : void
    {
      $this->unit_id =  $unit_id;
    }
    
    public function getCode(): string
    {
     return $this->code;
    }
    
    public function setCode(string $code) : void
    {
      $this->code =  $code;
    }
    
    public function getDescription(): string
    {
     return $this->description;
    }
    
    public function setDescription(string $description) : void
    {
      $this->description =  $description;
    }
    
    public function getName(): string
    {
     return $this->name;
    }
    
    public function setName(string $name) : void
    {
      $this->name =  $name;
    }
 }