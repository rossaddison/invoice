<?php

declare(strict_types=1);

namespace App\Invoice\QuoteAmount;

use Yiisoft\FormModel\FormModel;
use Yiisoft\Validator\Rule\Required;

final class QuoteAmountForm extends FormModel
{
    private ?int $quote_id = null;
    private ?float $item_subtotal = null;
    private ?float $item_tax_total = null;
    private ?float $packhandleship_total = null;
    private ?float $packhandleship_tax = null;
    private ?float $tax_total = null;
    private ?float $total = null;

    public function getQuote_id(): ?int
    {
        return $this->quote_id;
    }

    public function getItem_subtotal(): ?float
    {
        return $this->item_subtotal;
    }

    public function getItem_tax_total(): ?float
    {
        return $this->item_tax_total;
    }
    
    public function getPackhandleship_total(): ?float
    {
        return $this->packhandleship_total;
    }

    public function getPackhandleship_tax(): ?float
    {
        return $this->packhandleship_tax;
    }

    public function getTax_total(): ?float
    {
        return $this->tax_total;
    }

    public function getTotal(): ?float
    {
        return $this->total;
    }

    /**
     * @return string
     *
     * @psalm-return ''
     */
    #[\Override]
    public function getFormName(): string
    {
        return '';
    }

    /**
     * @return Required[][]
     *
     * @psalm-return array{item_subtotal: list{Required}, item_tax_total: list{Required}, packhandleship_total: list{Required}, packhandleship_tax: list{Required}, tax_total: list{Required}, total: list{Required}, quote_id: list{Required}}
     */
    public function getRules(): array
    {
        return [
            'item_subtotal' => [new Required()],
            'item_tax_total' => [new Required()],            
            'packhandleship_total' => [new Required()],
            'packhandleship_tax' => [new Required()],
            'tax_total' => [new Required()],
            'total' => [new Required()],
            'quote_id' => [new Required()],
        ];
    }
}
