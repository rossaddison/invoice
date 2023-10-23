<?php

declare(strict_types=1); 

namespace App\Invoice\Entity;

use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Entity;
use Cycle\Annotated\Annotation\Relation\BelongsTo;
use App\Invoice\Entity\CustomField;
use App\Invoice\Entity\SalesOrder;

#[Entity(repository: \App\Invoice\SalesOrderCustom\SalesOrderCustomRepository::class)]
class SalesOrderCustom
{ 
    #[BelongsTo(target:CustomField::class, nullable: false)]
    private ?CustomField $custom_field = null;

    #[BelongsTo(target:SalesOrder::class, nullable: false)]
    private ?SalesOrder $so = null;
    
    #[Column(type: 'primary')]
    private ?int $id =  null;
    
    #[Column(type: 'integer(11)', nullable: false)]
    private ?int $so_id =  null;
     
    #[Column(type: 'integer(11)', nullable: false)]
    private ?int $custom_field_id =  null;
    
    #[Column(type: 'text', nullable: true)]
    private string $value =  '';
     
    public function __construct(
        int $id = null,
        int $so_id = null,
        int $custom_field_id = null,
        string $value = ''
    )
    {
        $this->id=$id;
        $this->so_id=$so_id;
        $this->custom_field_id=$custom_field_id;
        $this->value=$value;
    }
    
    public function getCustomField() : ?CustomField
    {
      return $this->custom_field;
    }
    
    public function getSalesOrder() : ?SalesOrder
    {
      return $this->so;
    }
    
    public function getId(): string
    {
     return (string)$this->id;
    }
    
    public function setId(int $id) : void
    {
      $this->id =  $id;
    }
    
    public function getSo_id(): string
    {
     return (string)$this->so_id;
    }
    
    public function setSo_id(int $so_id) : void
    {
      $this->so_id =  $so_id;
    }
    
    public function getCustom_field_id(): string
    {
     return (string)$this->custom_field_id;
    }
    
    public function setCustom_field_id(int $custom_field_id) : void
    {
      $this->custom_field_id =  $custom_field_id;
    }
    
    public function getValue(): string
    {
       return $this->value;
    }
    
    public function setValue(string $value) : void
    {
      $this->value =  $value;
    }
}