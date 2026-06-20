<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\InvItem\Trait;

use App\Infrastructure\Persistence\Inv\Inv;
use App\Infrastructure\Persistence\Product\Product;
use App\Infrastructure\Persistence\Task\Task;
use App\Infrastructure\Persistence\TaxRate\TaxRate;
use DateTime;
use DateTimeImmutable;

/**
 * @method int requireId(?int $id, string $context)
 */
trait InvItemTrait3
{

    public function setDiscountAmount(float $discount_amount): void
    {
        $this->discount_amount = $discount_amount;
    }

    // which extends this entity by means of inv_item_id

    public function getOrder(): ?int
    {
        return $this->order;
    }

    public function setOrder(int $order): void
    {
        $this->order = $order;
    }

    public function getIsRecurring(): ?bool
    {
        return $this->is_recurring;
    }

    public function setIsRecurring(bool $is_recurring): void
    {
        $this->is_recurring = $is_recurring;
    }

    public function getDate(): DateTimeImmutable
    {
        return $this->date;
    }

    public function setDate(DateTimeImmutable $date): void
    {
        $this->date = $date;
    }

    public function getProductUnit(): ?string
    {
        return $this->product_unit;
    }

    public function setProductUnit(string $product_unit): void
    {
        $this->product_unit = $product_unit;
    }

    public function getProductUnitId(): ?int
    {
        return $this->product_unit_id;
    }

    public function setProductUnitId(int $product_unit_id): void
    {
        $this->product_unit_id = $product_unit_id;
    }

    public function setBelongsToVatInvoice(int $belongs_to_vat_invoice): void
    {
        $this->belongs_to_vat_invoice = $belongs_to_vat_invoice;
    }

    public function getBelongsToVatInvoice(): string
    {
        return (string) $this->belongs_to_vat_invoice;
    }

    public function getDeliveryId(): ?int
    {
        return $this->delivery_id;
    }

    public function setDeliveryId(int $delivery_id): void
    {
        $this->delivery_id = $delivery_id;
    }

    public function getNote(): ?string
    {
        return $this->note;
    }
}
