<?php

declare(strict_types=1);

namespace App\Invoice\QuoteItemAmount;

use Yiisoft\Form\FormModel;
use Yiisoft\Validator\Rule\Required;

final class QuoteItemAmountForm extends FormModel
{    
    
    private ?int $quote_item_id=null;
    private ?float $subtotal=null;
    private ?float $tax_total=null;
    private ?float $discount=null;
    private ?float $total=null;

    public function getQuote_item_id() : int|null
    {
      return $this->quote_item_id;
    }

    public function getSubtotal() : float|null
    {
      return $this->subtotal;
    }

    public function getTax_total() : float|null
    {
      return $this->tax_total;
    }

    public function getDiscount() : float|null
    {
      return $this->discount;
    }

    public function getTotal() : float|null
    {
      return $this->total;
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
     * @psalm-return array{subtotal: list{Required}, tax_total: list{Required}, discount: list{Required}, total: list{Required}}
     */
    public function getRules(): array    {
      return [
        'subtotal' => [new Required()],
        'tax_total' => [new Required()],
        'discount' => [new Required()],
        'total' => [new Required()],
    ];
}
}
