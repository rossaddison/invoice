<?php

declare(strict_types=1);

namespace App\Invoice\QuoteAmount;

use Yiisoft\Form\FormModel;
use Yiisoft\Validator\Rule\Required;

final class QuoteAmountForm extends FormModel
{       
    private ?int $quote_id=null;
    private ?float $item_subtotal=null;
    private ?float $item_tax_total=null;
    private ?float $tax_total=null;
    private ?float $total=null;

    public function getQuote_id() : int|null
    {
      return $this->quote_id;
    }

    public function getItem_subtotal() : float|null
    {
      return $this->item_subtotal;
    }

    public function getItem_tax_total() : float|null
    {
      return $this->item_tax_total;
    }

    public function getTax_total() : float|null
    {
      return $this->tax_total;
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
     * @psalm-return array{item_subtotal: list{Required}, item_tax_total: list{Required}, tax_total: list{Required}, total: list{Required}, quote_id: list{Required}}
     */
    public function getRules(): array    {
      return [
        'item_subtotal' => [new Required()],
        'item_tax_total' => [new Required()],
        'tax_total' => [new Required()],
        'total' => [new Required()],
        'quote_id' => [new Required()],
    ];
}
}
