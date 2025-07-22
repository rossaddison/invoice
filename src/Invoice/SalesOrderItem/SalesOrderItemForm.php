<?php

declare(strict_types=1);

namespace App\Invoice\SalesOrderItem;

use App\Invoice\Entity\SalesOrderItem as SoItem;
use Yiisoft\FormModel\FormModel;
use Yiisoft\Validator\Rule\Required;

final class SalesOrderItemForm extends FormModel
{
    private ?string $id               = '';
    private ?string $so_id            = '';
    private ?string $peppol_po_itemid = '';
    private ?string $peppol_po_lineid = '';

    #[Required]
    private ?string $tax_rate_id = '';

    #[Required]
    private ?string $product_id  = '';
    private mixed $date_added    = '';
    private ?string $name        = '';
    private ?string $description = '';

    #[Required]
    private ?float $quantity = null;

    #[Required]
    private ?float $price = null;

    #[Required]
    private ?float $discount_amount = null;

    #[Required]
    private ?float $charge_amount = null;

    private ?int $order           = null;
    private ?string $product_unit = '';

    #[Required]
    private ?int $product_unit_id = null;

    public function __construct(SoItem $salesOrderItem)
    {
        $this->id = $salesOrderItem->getId();

        // https://docs.peppol.eu/poacc/billing/3.0/syntax/ubl-invoice/cac-InvoiceLine/cac-Item/cac-BuyersItemIdentification/
        $this->peppol_po_itemid = $salesOrderItem->getPeppol_po_itemid();

        // https://docs.peppol.eu/poacc/billing/3.0/syntax/ubl-invoice/cac-InvoiceLine/cac-OrderLineReference/
        $this->peppol_po_lineid = $salesOrderItem->getPeppol_po_lineid();

        $this->date_added      = $salesOrderItem->getDate_added();
        $this->name            = $salesOrderItem->getName();
        $this->description     = $salesOrderItem->getDescription();
        $this->quantity        = $salesOrderItem->getQuantity();
        $this->price           = $salesOrderItem->getPrice();
        $this->discount_amount = $salesOrderItem->getDiscount_amount();
        $this->order           = $salesOrderItem->getOrder();
        $this->product_unit    = $salesOrderItem->getProduct_unit();
        $this->so_id           = $salesOrderItem->getSales_order_id();
        $this->tax_rate_id     = $salesOrderItem->getTax_rate_id();
        $this->product_id      = $salesOrderItem->getProduct_id();
        $this->product_unit_id = (int) $salesOrderItem->getProduct_unit_id();
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getSo_id(): ?string
    {
        return $this->so_id;
    }

    public function getPeppol_po_itemid(): ?string
    {
        return $this->peppol_po_itemid;
    }

    public function getPeppol_po_lineid(): ?string
    {
        return $this->peppol_po_lineid;
    }

    public function getTax_rate_id(): ?string
    {
        return $this->tax_rate_id;
    }

    public function getProduct_id(): ?string
    {
        return $this->product_id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getQuantity(): ?float
    {
        return $this->quantity;
    }

    public function getPrice(): ?float
    {
        return $this->price;
    }

    public function getDiscount_amount(): ?float
    {
        return $this->discount_amount;
    }

    public function getCharge_amount(): ?float
    {
        return $this->charge_amount;
    }

    public function getOrder(): ?int
    {
        return $this->order;
    }

    public function getProduct_unit(): ?string
    {
        return $this->product_unit;
    }

    public function getProduct_unit_id(): ?int
    {
        return $this->product_unit_id;
    }

    /**
     * @psalm-return ''
     */
    #[\Override]
    public function getFormName(): string
    {
        return '';
    }
}
