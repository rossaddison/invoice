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
    public ?string $inv_id = '';
    public ?string $so_item_id = '';

    #[Required]
    public ?string $tax_rate_id = '';

    public ?string $product_id = '';

    public ?string $task_id = '';
    // Then name of the product is retrieved using the users product_id dropdown list choice
    public ?string $name = '';

    public ?string $description = '';

    public ?string $note = '';

    #[GreaterThan(0.00)]
    public ?float $quantity = null;

    #[GreaterThan(0.00)]
    public ?float $price = null;

    public ?float $discount_amount = null;

    public ?int $order = null;
    public ?string $product_unit = '';

    /**
     * Not Required because will conflict with a task which does not require a product unit id.
     * To cause an error and test the InvController function invToInvItems use the inv/index checkbox Copy Invoice button
     * on an invoice that has both a task and a product and input a #[Required] here. Result: danger flash message in inv/index
     */
    public ?int $product_unit_id = null;

    public ?string $date = null;

    public ?int $belongs_to_vat_invoice = null;
    public ?int $delivery_id = null;
    public ?string $peppol_po_itemid = '';
    public ?string $peppol_po_lineid = '';

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
        $form->peppol_po_itemid = $invitem->getPeppolPoItemid();
        $form->peppol_po_lineid = $invitem->getPeppolPoLineid();
        return $form;
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
