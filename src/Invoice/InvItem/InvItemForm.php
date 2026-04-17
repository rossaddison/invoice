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
     * To cause an error and test the InvController function invToInvItems use the inv/index checkbox Copy Invoice button
     * on an invoice that has both a task and a product and input a #[Required] here. Result: danger flash message in inv/index
     */
    private ?int $product_unit_id = null;

    #[Required]
    private readonly DateTimeImmutable $date;

    private ?int $belongs_to_vat_invoice = null;
    private ?string $delivery_id = '';

    public function __construct(InvItem $invitem, int $inv_id)
    {
        $this->inv_id = (string) $inv_id;
        $this->so_item_id = $invitem->getSoItemId();
        $this->tax_rate_id = $invitem->getTaxRateId();
        $this->product_id = $invitem->getProductId();
        $this->task_id = $invitem->getTaskId();
        $this->name = $invitem->getName();
        $this->description = $invitem->getDescription();
        $this->note = $invitem->getNote();
        $this->quantity = $invitem->getQuantity();
        $this->price = $invitem->getPrice();
        $this->discount_amount = $invitem->getDiscountAmount();
        $this->order = (string) $invitem->getOrder();
        $this->product_unit = $invitem->getProductUnit();
        $this->product_unit_id = (int) $invitem->getProductUnitId();
        $this->date = $invitem->getDate();
        $this->belongs_to_vat_invoice = (int) $invitem->getBelongsToVatInvoice();
        $this->delivery_id = $invitem->getDeliveryId();
    }

    public function getDate(): DateTimeImmutable
    {
        return $this->date;
    }

    public function getInvId(): ?string
    {
        return $this->inv_id;
    }

    public function getSoItemId(): ?string
    {
        return $this->so_item_id;
    }

    public function getTaxRateId(): ?string
    {
        return $this->tax_rate_id;
    }

    public function getProductId(): ?string
    {
        return $this->product_id;
    }

    public function getTaskId(): ?string
    {
        return $this->task_id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getNote(): ?string
    {
        return $this->note;
    }

    public function getQuantity(): ?float
    {
        return $this->quantity;
    }

    public function getPrice(): ?float
    {
        return $this->price;
    }

    public function getDiscountAmount(): ?float
    {
        return $this->discount_amount;
    }

    public function getOrder(): ?string
    {
        return $this->order;
    }

    public function getProductUnit(): ?string
    {
        return $this->product_unit;
    }

    public function getProductUnitId(): ?int
    {
        return $this->product_unit_id;
    }

    public function getBelongsToVatInvoice(): ?int
    {
        return $this->belongs_to_vat_invoice;
    }

    public function getDeliveryId(): ?string
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
