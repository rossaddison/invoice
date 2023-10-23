<?php
declare(strict_types=1);

namespace App\Invoice\InvTaxRate;

use Yiisoft\Form\FormModel;
use Yiisoft\Validator\Rule\Required;

final class InvTaxRateForm extends FormModel
{        
    private ?string $inv_id='';
    private ?string $tax_rate_id='';
    private ?int $include_item_tax=null;
    private ?float $inv_tax_rate_amount=null;

    public function getInv_id() : string|null
    {
      return $this->inv_id;
    }

    public function getTax_rate_id() : string|null
    {
      return $this->tax_rate_id;
    }

    public function getInclude_item_tax() : int|null
    {
      return $this->include_item_tax;
    }

    public function getInv_tax_rate_amount() : float|null
    {
      return $this->inv_tax_rate_amount;
    }

    /**
     * @return string
     *
     * @psalm-return ''
     */
    public function getFormName(): string
    {
      return '';
    }

    /**
     * @return Required[][]
     *
     * @psalm-return array{include_item_tax: list{Required}, inv_tax_rate_amount: list{Required}}
     */
    public function getRules(): array    {
      return [
        'include_item_tax' => [new Required()],
        'inv_tax_rate_amount' => [new Required()],
    ];
}
}
