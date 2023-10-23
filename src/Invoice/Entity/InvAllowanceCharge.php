<?php
declare(strict_types=1); 

namespace App\Invoice\Entity;

use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Entity;
use Cycle\Annotated\Annotation\Relation\BelongsTo;
use App\Invoice\Entity\AllowanceCharge;
  
 #[Entity(repository: \App\Invoice\InvAllowanceCharge\InvAllowanceChargeRepository::class)]
 
 class InvAllowanceCharge
 {
     #[BelongsTo(target:AllowanceCharge::class, nullable: false, fkAction:'NO ACTION')]
     private ?AllowanceCharge $allowance_charge = null;
     
     #[Column(type:'primary')]
     private ?int $id =  null;
     
     #[Column(type:'integer(11)', nullable: false)]
     private ?int $inv_id =  null;
         
     #[Column(type:'integer(11)', nullable: false)]
     private ?int $allowance_charge_id =  null;
     
     #[Column(type:'decimal(20,2)', nullable: false, default: 0.00)]
     private ?float $amount =  null;
     
     #[Column(type: 'decimal(20,2)', nullable: false, default: 0.00)]
     private ?float $vat = 0.00;
     
     public function __construct(
         int $id = null,
         int $inv_id = null,
         int $allowance_charge_id = null,
         float $amount = null,
         float $vat = null    
     )
     {
         $this->id=$id;
         $this->inv_id=$inv_id;
         $this->allowance_charge_id=$allowance_charge_id;
         $this->amount=$amount;
         $this->vat=$vat;
     }
    
    public function getAllowanceCharge() : ?AllowanceCharge
    {
      return $this->allowance_charge;
    }
    
    public function setAllowanceCharge(?AllowanceCharge $allowance_charge): void
    {
      $this->allowance_charge = $allowance_charge;
    }
    
    public function getId(): string
    {
     return (string)$this->id;
    }
    
    public function setId(int $id) : void
    {
      $this->id =  $id;
    }
    
    public function getInv_id(): string
    {
     return (string)$this->inv_id;
    }
    
    public function setInv_id(int $inv_id) : void
    {
      $this->inv_id =  $inv_id;
    }
    
    public function getAllowance_charge_id(): string
    {
     return (string)$this->allowance_charge_id;
    }
    
    public function setAllowance_charge_id(int $allowance_charge_id) : void
    {
      $this->allowance_charge_id =  $allowance_charge_id;
    }
    
    public function getAmount(): string
    {
        return (string)$this->amount;
    }
    
    public function setAmount(float $amount): void
    {
        $this->amount = $amount;
    }
    
    public function getVat(): string
    {
        return (string)$this->vat;
    }
    
    public function setVat(float $vat): void
    {
        $this->vat = $vat;
    }
    
    public function nullifyRelationOnChange(int $allowance_charge_id) : void 
    {
        if ($this->allowance_charge_id <> $allowance_charge_id) {
            $this->allowance_charge = null;
        }
    }
}