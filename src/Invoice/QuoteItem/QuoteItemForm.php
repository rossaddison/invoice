<?php

declare(strict_types=1);

namespace App\Invoice\QuoteItem;

use Yiisoft\Form\FormModel;
use Yiisoft\Validator\Rule\Required;

final class QuoteItemForm extends FormModel
{        
    private ?string $quote_id='';
    private ?string $tax_rate_id='';
    private ?string $product_id='';
    private ?string $name='';
    private ?string $description='';
    private ?float $quantity=null;
    private ?float $price=null;
    private ?float $discount_amount=null;
    private ?int $order=null;
    private ?string $product_unit='';
    private ?int $product_unit_id=null;

    public function getQuote_id() : string|null
    {
      return $this->quote_id;
    }

    public function getTax_rate_id(): string|null
    {
        return $this->tax_rate_id;
    }

    public function getProduct_id() : string|null
    {
      return $this->product_id;
    }

    public function getName() : string|null
    {
      return $this->name;
    }

    public function getDescription() : string|null
    {
      return $this->description;
    }

    public function getQuantity() : float|null
    {
      return $this->quantity;
    }

    public function getPrice() : float|null
    {
      return $this->price;
    }

    public function getDiscount_amount() : float|null
    {
      return $this->discount_amount;
    }

    public function getOrder() : int|null
    {
      return $this->order;
    }

    public function getProduct_unit() : string|null
    {
      return $this->product_unit;
    }

    public function getProduct_unit_id() : int|null
    {
      return $this->product_unit_id;
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
     * @psalm-return array{tax_rate_id: list{Required}, product_id: list{Required}, quantity: list{Required}, price: list{Required}, discount_amount: list{Required}, order: list{Required}, product_unit_id: list{Required}}
     */
    public function getRule(): array    {
      return [
        'tax_rate_id' => [new Required()],
        'product_id' => [new Required()],
        'quantity' => [new Required()],
        'price' => [new Required()],
        'discount_amount' => [new Required()],
        'order' => [new Required()],
        'product_unit_id' => [new Required()],
    ];
    }
}
