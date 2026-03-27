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

    private ?int $order = null;
    #[Length(min: 0, max: 50, skipOnEmpty: true)]
    private ?string $product_unit = '';

    private ?int $product_unit_id = null;

    public function __construct(SoItem $salesOrderItem, private readonly ?string $so_id )
    {
        $this->id = $salesOrderItem->getId();
        //https://docs.peppol.eu/poacc/billing/3.0/syntax/ubl-invoice/cac-InvoiceLine/cac-Item/cac-BuyersItemIdentification/
        $this->peppol_po_itemid = $salesOrderItem->getPeppolPoItemid();

        //https://docs.peppol.eu/poacc/billing/3.0/syntax/ubl-invoice/cac-InvoiceLine/cac-OrderLineReference/
        $this->peppol_po_lineid = $salesOrderItem->getPeppolPoLineid();
        $this->name = $salesOrderItem->getName();
        $this->description = $salesOrderItem->getDescription();
        $this->quantity = $salesOrderItem->getQuantity();
        $this->price = $salesOrderItem->getPrice();
        $this->discount_amount = $salesOrderItem->getDiscountAmount();
        $this->order = $salesOrderItem->getOrder();
        $this->product_unit = $salesOrderItem->getProductUnit();  
        $this->tax_rate_id = $salesOrderItem->getTaxRateId();
        $this->product_id = $salesOrderItem->getProductId();
        $this->task_id = $salesOrderItem->getTaskId();
        $this->product_unit_id = (int) $salesOrderItem->getProductUnitId();
        $this->date_added = $salesOrderItem->getDateAdded();
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getSoId(): ?string
    {
        return $this->so_id;
    }

    public function getPeppolPoItemid(): ?string
    {
        return $this->peppol_po_itemid;
    }

    public function getPeppolPoLineid(): ?string
    {
        return $this->peppol_po_lineid;
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
