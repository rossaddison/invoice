<?php
declare(strict_types=1);

namespace App\Invoice\QuoteTaxRate;

use Yiisoft\Form\FormModel;
use Yiisoft\Validator\Rule\Required;

final class QuoteTaxRateForm extends FormModel
{    
    private ?int $quote_id=null;
    private ?int $tax_rate_id=null;
    private ?int $include_item_tax=null;
    private ?float $quote_tax_rate_amount=null;

    public function getQuote_id() : int|null
    {
      return $this->quote_id;
    }

    public function getTax_rate_id() : int|null
    {
      return $this->tax_rate_id;
    }

    public function getInclude_item_tax() : int|null
    {
      return $this->include_item_tax;
    }

    public function getQuote_tax_rate_amount() : float
    {
      return $this->quote_tax_rate_amount ?? 0.00;
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
