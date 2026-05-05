<?php

declare(strict_types=1);

namespace App\Invoice\InvItem;

use App\Infrastructure\Persistence\{
    InvItem\InvItem
};
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

    private ?int $order = null;
    private ?string $product_unit = '';

    /**
     * Not Required because will conflict with a task which does not require a product unit id.
     * To cause an error and test the InvController function invToInvItems use the inv/index checkbox Copy Invoice button
     * on an invoice that has both a task and a product and input a #[Required] here. Result: danger flash message in inv/index
     */
    private ?int $product_unit_id = null;

    private ?string $date = null;

    private ?int $belongs_to_vat_invoice = null;
    private ?int $delivery_id = null;
    
    public static function show(InvItem $invitem, int $inv_id): self
    {
        $form = new self();
        $form->inv_id = (string) $inv_id;
        $form->so_item_id = (string) $invitem->getSoItemId();
        $form->tax_rate_id = (string) $invitem->reqTaxRateId();
        $form->product_id = null !== ($product = $invitem->getProduct()) ?
            (string) $product->reqId() : null;
        $form->task_id = null !== ($task = $invitem->getTask()) ?
            (string) $task->reqId() : null;
        $form->name = $invitem->getName();
        $form->description = $invitem->getDescription();
        $form->note = $invitem->getNote();
        $form->quantity = $invitem->getQuantity();
        $form->price = $invitem->getPrice();
        $form->discount_amount = $invitem->getDiscountAmount();
        $form->order = $invitem->getOrder();
        $form->product_unit = $invitem->getProductUnit();
        $form->product_unit_id = $invitem->getProductUnitId();
        $form->date = $invitem->getDate() instanceof DateTimeImmutable ?
                $invitem->getDate()->format('Y-m-d') : null;
        $form->belongs_to_vat_invoice = (int) $invitem->getBelongsToVatInvoice();
        $form->delivery_id = $invitem->getDeliveryId();
        return $form;
    }

    public function getDate(): ?string
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

    public function getOrder(): ?int
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

    public function getDeliveryId(): ?int
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
