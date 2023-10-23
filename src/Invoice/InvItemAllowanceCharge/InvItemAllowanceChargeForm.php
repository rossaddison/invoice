<?php

declare(strict_types=1);

namespace App\Invoice\InvItemAllowanceCharge;

use Yiisoft\Form\FormModel;
use Yiisoft\Validator\Rule\Required;

final class InvItemAllowanceChargeForm extends FormModel
{  
    private ?int $inv_id=null;
    private ?int $inv_item_id=null;
    private ?int $allowance_charge_id=null;
    private ?float $amount=null;
    private ?float $vat=null;

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

    public function getRules(): array    {
      return [
        'allowance_charge_id' => [new Required()],  
        'amount' => [new Required()],
        'vat' => [new Required()],
      ];
}
}
