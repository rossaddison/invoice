<?php

declare(strict_types=1); 

namespace App\Invoice\Entity;

use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Entity;
use Cycle\Annotated\Annotation\Relation\BelongsTo;
use App\Invoice\Entity\Product;
use App\Invoice\Entity\CustomField;

#[Entity(repository: \App\Invoice\ProductCustom\ProductCustomRepository::class)] 
class ProductCustom
{
    #[BelongsTo(target:Product::class, nullable: false)]
    private ?Product $product = null;
      
    #[BelongsTo(target: CustomField::class, nullable: false)]
    private ?CustomField $custom_field = null;
        
    #[Column(type: 'primary')]
    private ?int $id =  null;
    
    #[Column(type: 'integer(11)', nullable: false)]
    private ?int $product_id =  null;
     
    #[Column(type: 'integer(11)', nullable: false)]
    private ?int $custom_field_id =  null;
    
    #[Column(type: 'text', nullable: true)]
    private ?string $value =  '';
     
    public function __construct(
         int $id = null,
         int $product_id = null,
         int $custom_field_id = null,
         string $value = ''
    )
    {
         $this->id=$id;
         $this->product_id=$product_id;
         $this->custom_field_id=$custom_field_id;
         $this->value=$value;
    }
    
    public function getProduct() : ?Product
    {
      return $this->product;
    }
    
    public function getCustomField() : ?CustomField
    {
      return $this->custom_field;
    }
    
    public function getId(): string
    {
     return (string)$this->id;
    }
    
    public function setId(int $id) : void
    {
      $this->id =  $id;
    }
    
    public function getProduct_id(): string
    {
     return (string)$this->product_id;
    }
    
    public function setProduct_id(int $product_id) : void
    {
      $this->product_id =  $product_id;
    }
    
    public function getCustom_field_id(): string
    {
     return (string)$this->custom_field_id;
    }
    
    public function setCustom_field_id(int $custom_field_id) : void
    {
      $this->custom_field_id =  $custom_field_id;
    }
    
    public function getValue(): ?string
    {
       return $this->value;
    }
    
    public function setValue(string $value) : void
    {
      $this->value =  $value;
    }
}