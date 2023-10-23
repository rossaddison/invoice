<?php
declare(strict_types=1);

namespace App\Invoice\InvAllowanceCharge;

use Yiisoft\Form\FormModel;

final class InvAllowanceChargeForm extends FormModel
{    
    private ?int $id=null;
    private ?int $inv_id=null;
    private ?int $allowance_charge_id=null;
    private ?int $amount=null;
    private ?int $vat=null;
    
    public function getId() : int|null
    {
      return $this->id;
    }

    public function getInv_id() : int|null
    {
      return $this->inv_id;
    }

    public function getAllowance_charge_id() : int|null
    {
      return $this->allowance_charge_id;
    }
    
    public function getAmount(): int|null
    {
      return $this->amount; 
    }
    
    public function getVat(): int|null
    {
      return $this->vat; 
    }

    /**
     * @return string
     * @psalm-return ''
     */
    public function getFormName(): string
    {
      return '';
    }

    public function getRules(): array    {
      return [
    ];
}
}
