<?php
declare(strict_types=1);

namespace App\Invoice\SalesOrderTaxRate;

use Yiisoft\Form\FormModel;
use Yiisoft\Validator\Rule\Required;

final class SalesOrderTaxRateForm extends FormModel
{    
    private ?int $so_id=null;
    private ?int $tax_rate_id=null;
    private ?int $include_item_tax=null;
    private ?float $so_tax_rate_amount=null;

    public function getSo_id() : int|null
    {
      return $this->so_id;
    }

    public function getTax_rate_id() : int|null
    {
      return $this->tax_rate_id;
    }

    public function getInclude_item_tax() : int|null
    {
      return $this->include_item_tax;
    }

    public function getSo_tax_rate_amount() : float
    {
      return $this->so_tax_rate_amount ?? 0.00;
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
     * @psalm-return array{tax_rate_id: list{Required}}
     */
    public function getRules(): array  {
      return [
         'tax_rate_id'=> [new Required()],
      ];
    }
}
