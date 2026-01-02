<?php

declare(strict_types=1);

namespace App\Invoice\SalesOrderItem;

use App\Invoice\Entity\SalesOrderItem as SoItem;
use Yiisoft\FormModel\FormModel;
use Yiisoft\Validator\Rule\Length;
use Yiisoft\Validator\Rule\Required;

final class SalesOrderItemForm extends FormModel
{
    private ?string $id = '';
    #[Length(min: 0, max: 50, skipOnEmpty: true)]
    private ?string $peppol_po_itemid = '';
    #[Length(min: 0, max: 50, skipOnEmpty: true)]
    private ?string $peppol_po_lineid = '';

    #[Required]
    private ?string $tax_rate_id = '';
    private ?string $product_id = '';
    private ?string $task_id = '';
    private mixed $date_added = '';
    #[Length(min: 0, max: 200, skipOnEmpty: true)]
    private ?string $name = '';
    #[Length(min: 0, max: 1000, skipOnEmpty: true)]
    private ?string $description = '';

    #[Required]
    private ?float $quantity = null;

    #[Required]
    private ?float $price = null;

    #[Required]
    private ?float $discount_amount = null;

    #[Required]
    private ?float $charge_amount = null;

    private ?int $order = null;
    #[Length(min: 0, max: 50, skipOnEmpty: true)]
    private ?string $product_unit = '';

    private ?int $product_unit_id = null;

    public function __construct(SoItem $salesOrderItem, private readonly ?string $so_id )
    {
        $this->id = $salesOrderItem->getId();
        //https://docs.peppol.eu/poacc/billing/3.0/syntax/ubl-invoice/cac-InvoiceLine/cac-Item/cac-BuyersItemIdentification/
        $this->peppol_po_itemid = $salesOrderItem->getPeppol_po_itemid();

        //https://docs.peppol.eu/poacc/billing/3.0/syntax/ubl-invoice/cac-InvoiceLine/cac-OrderLineReference/
        $this->peppol_po_lineid = $salesOrderItem->getPeppol_po_lineid();
        $this->name = $salesOrderItem->getName();
        $this->description = $salesOrderItem->getDescription();
        $this->quantity = $salesOrderItem->getQuantity();
        $this->price = $salesOrderItem->getPrice();
        $this->discount_amount = $salesOrderItem->getDiscount_amount();
        $this->charge_amount = $salesOrderItem->getCharge_amount();
        $this->order = $salesOrderItem->getOrder();
        $this->product_unit = $salesOrderItem->getProduct_unit();  
        $this->tax_rate_id = $salesOrderItem->getTax_rate_id();
        $this->product_id = $salesOrderItem->getProduct_id();
        $this->task_id = $salesOrderItem->getTask_id();
        $this->product_unit_id = (int) $salesOrderItem->getProduct_unit_id();
        $this->date_added = $salesOrderItem->getDate_added();
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
    
    public function getTask_id(): ?string
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
