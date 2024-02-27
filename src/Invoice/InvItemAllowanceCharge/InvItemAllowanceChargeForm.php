<?php

declare(strict_types=1);

namespace App\Invoice\InvItemAllowanceCharge;

use App\Invoice\Entity\InvItemAllowanceCharge;
use Yiisoft\FormModel\FormModel;
use Yiisoft\Validator\Rule\Required;

final class InvItemAllowanceChargeForm extends FormModel
{  
    private ?int $inv_id=null;
    private ?int $inv_item_id=null;
    #[Required]
    private ?int $allowance_charge_id=null;
    #[Required]
    private ?float $amount=null;
    #[Required]
    private ?float $vat=null;
    
    public function __construct(InvItemAllowanceCharge $invItemAllowanceCharge, int $inv_item_id)
    {
        $this->inv_id = (int)$invItemAllowanceCharge->getInv_id();
        $this->inv_item_id = $inv_item_id;
        $this->allowance_charge_id = (int)$invItemAllowanceCharge->getAllowance_charge_id();
        $this->amount = (float)$invItemAllowanceCharge->getAmount();
        $this->vat = (float)$invItemAllowanceCharge->getVat();
    }        

    public function getInv_id() : int|null
    {
      return $this->inv_id;
    }

    public function getInv_item_id() : int|null
    {
      return $this->inv_item_id;
    }

    public function getAllowance_charge_id() : int|null
    {
      return $this->allowance_charge_id;
    }

    public function getAmount() : float|null
    {
      return $this->amount;
    }

    public function getVat() : float|null
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

}
