<?php

declare(strict_types=1);

namespace App\Invoice\InvItem;

use App\Invoice\Entity\InvItem;
use Yiisoft\FormModel\FormModel;
use Yiisoft\Validator\Rule\Required;
use Yiisoft\Validator\Rule\GreaterThan;
use DateTimeImmutable;

final class InvItemForm extends FormModel
{
    private ?string $id = '';
    private ?string $inv_id = '';
    private ?string $so_item_id = '';

    #[Required]
    private ?string $tax_rate_id = '';

    private ?string $product_id = '';

    private ?string $task_id = '';
    // Then name of the product is retrieved using the users product_id dropdown list choice
    private ?string $name = '';

    private ?string $description = '';

    private ?string $note = '';

    #[GreaterThan(0.00)]
    private ?float $quantity = null;

    #[GreaterThan(0.00)]
    private ?float $price = null;

    private ?float $discount_amount = null;

    private ?string $order = null;
    private ?string $product_unit = '';

    /**
     * Not Required because will conflict with a task which does not require a product unit id.
     * To cause an error and test the InvController function inv_to_inv_items use the inv/index checkbox Copy Invoice button
     * on an invoice that has both a task and a product and input a #[Required] here. Result: danger flash message in inv/index
     */
    private ?int $product_unit_id = null;

    #[Required]
    private readonly DateTimeImmutable $date;

    private ?int $belongs_to_vat_invoice = null;
    private ?string $delivery_id = '';

    public function __construct(InvItem $invitem, int $inv_id)
    {
        $this->id = (string) $invitem->getId();
        $this->inv_id = (string) $inv_id;
        $this->so_item_id = $invitem->getSo_item_id();
        $this->tax_rate_id = $invitem->getTax_rate_id();
        $this->product_id = $invitem->getProduct_id();
        $this->task_id = $invitem->getTask_id();
        $this->name = $invitem->getName();
        $this->description = $invitem->getDescription();
        $this->note = $invitem->getNote();
        $this->quantity = $invitem->getQuantity();
        $this->price = $invitem->getPrice();
        $this->discount_amount = $invitem->getDiscount_amount();
        $this->order = (string) $invitem->getOrder();
        $this->product_unit = $invitem->getProduct_unit();
        $this->product_unit_id = (int) $invitem->getProduct_unit_id();
        $this->date = $invitem->getDate();
        $this->belongs_to_vat_invoice = (int) $invitem->getBelongs_to_vat_invoice();
        $this->delivery_id = $invitem->getDelivery_id();
    }

    public function getId(): string|null
    {
        return $this->id;
    }

    public function getDate(): DateTimeImmutable
    {
        return $this->date;
    }

    public function getInv_id(): string|null
    {
        return $this->inv_id;
    }

    public function getSo_item_id(): string|null
    {
        return $this->so_item_id;
    }

    public function getTax_rate_id(): string|null
    {
        return $this->tax_rate_id;
    }

    public function getProduct_id(): string|null
    {
        return $this->product_id;
    }

    public function getTask_id(): string|null
    {
        return $this->task_id;
    }

    public function getName(): string|null
    {
        return $this->name;
    }

    public function getDescription(): string|null
    {
        return $this->description;
    }

    public function getNote(): string|null
    {
        return $this->note;
    }

    public function getQuantity(): float|null
    {
        return $this->quantity;
    }

    public function getPrice(): float|null
    {
        return $this->price;
    }

    public function getDiscount_amount(): float|null
    {
        return $this->discount_amount;
    }

    public function getOrder(): string|null
    {
        return $this->order;
    }

    public function getProduct_unit(): string|null
    {
        return $this->product_unit;
    }

    public function getProduct_unit_id(): int|null
    {
        return $this->product_unit_id;
    }

    public function getBelongs_to_vat_invoice(): int|null
    {
        return $this->belongs_to_vat_invoice;
    }

    public function getDelivery_id(): string|null
    {
        return $this->delivery_id;
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
}
