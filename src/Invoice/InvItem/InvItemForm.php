<?php

declare(strict_types=1);

namespace App\Invoice\InvItem;

use Yiisoft\Form\FormModel;
use Yiisoft\Validator\Rule\Required;

final class InvItemForm extends FormModel
{        
    private ?string $inv_id='';
    private ?string $so_item_id='';
    private ?string $tax_rate_id='';
    private ?string $product_id='';
    private ?string $task_id='';
    private ?string $name='';
    private ?string $description='';
    private ?string $note='';
    private ?float $quantity=null;
    private ?float $price=null;
    private ?float $discount_amount=null;
    private ?int $order=null;
    private ?string $product_unit='';
    private ?int $product_unit_id=null;
    private ?string $date='';
    private ?string $delivery_id='';
    
    public function getDate() : string|null
    {
        return $this->date;
    }
    
    public function getInv_id() : string|null
    {
      return $this->inv_id;
    }
    
    public function getSo_item_id() : string|null
    {
      return $this->so_item_id;
    }

    public function getTax_rate_id() : string|null
    {
      return $this->tax_rate_id;
    }

    public function getProduct_id() : string|null
    {
      return $this->product_id;
    }
    
    public function getTask_id() : string|null
    {
      return $this->task_id;
    }

    public function getName() : string|null
    {
      return $this->name;
    }

    public function getDescription() : string|null
    {
      return $this->description;
    }
    
    public function getNote() : string|null
    {
      return $this->note;
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
    
    public function getDelivery_id() : string|null
    {
      return $this->delivery_id;
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
     * @return array
     */
    public function getRule(): array {
        return [
          'tax_rate_id' => [new Required()],
          'product_id' => [new Required()],
          'task_id' => [new Required()],
          'quantity' => [new Required()],
          'price' => [new Required()],
          'discount_amount' => [new Required()],
          'order' => [new Required()],
          'product_unit_id' => [new Required()],
        ];
    }
}
