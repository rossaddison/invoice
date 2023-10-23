<?php

declare(strict_types=1); 

namespace App\Invoice\Entity;

use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Entity;
use Cycle\Annotated\Annotation\Relation\BelongsTo;
use App\Invoice\Entity\SalesOrder;
use App\Invoice\Entity\TaxRate;

#[Entity(repository: \App\Invoice\SalesOrderTaxRate\SalesOrderTaxRateRepository::class)] 

class SalesOrderTaxRate
{       
    #[BelongsTo(target:SalesOrder::class, nullable: false, fkAction: 'NO ACTION')]
    private ?SalesOrder $so = null;
    
    #[BelongsTo(target:TaxRate::class, nullable: false)]
    private ?TaxRate $tax_rate = null;    
    
    #[Column(type: 'primary')]
    private ?int $id =  null;
     
    #[Column(type: 'integer(11)', nullable: false)]
    private ?int $so_id =  null;
     
    #[Column(type: 'integer(11)', nullable: false)]
    private ?int $tax_rate_id =  null;
    
    #[Column(type: 'integer(1)', nullable: false, default:0)]
    private ?int $include_item_tax =  null;
    
    #[Column(type: 'decimal(20,2)', nullable: false, default: 0.00)]
    private ?float $so_tax_rate_amount =  0.00;
     
    public function __construct(
        int $id = null,
        int $so_id = null,
        int $tax_rate_id = null,
        int $include_item_tax = null,
        float $so_tax_rate_amount = 0.00
    )
    {
        $this->id=$id;
        $this->so_id=$so_id;
        $this->tax_rate_id=$tax_rate_id;
        $this->include_item_tax=$include_item_tax;
        $this->so_tax_rate_amount=$so_tax_rate_amount;
    }
    
    public function getSalesOrder() : ?SalesOrder
    {
      return $this->so;
    }
    
    public function getTaxRate() : ?TaxRate
    {
      return $this->tax_rate;
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
    
    public function getTax_rate_id(): string
    {
     return (string)$this->tax_rate_id;
    }
    
    public function setTax_rate_id(int $tax_rate_id) : void
    {
      $this->tax_rate_id =  $tax_rate_id;
    }
    
    public function getInclude_item_tax(): int|null
    {
       return $this->include_item_tax;
    }
    
    public function setInclude_item_tax(int $include_item_tax) : void
    {
      $this->include_item_tax =  $include_item_tax;
    }
    
    public function getSo_tax_rate_amount(): ?float
    {
       return $this->so_tax_rate_amount;
    }
    
    public function setSo_tax_rate_amount(float $so_tax_rate_amount) : void
    {
      $this->so_tax_rate_amount =  $so_tax_rate_amount;
    }
}