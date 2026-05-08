<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\SalesOrderCustom;

use App\Infrastructure\Persistence\SalesOrder\SalesOrder;
use App\Infrastructure\Persistence\CustomField\CustomField;
use App\Infrastructure\Persistence\Trait\RequireId;
use App\Invoice\SalesOrderCustom\SalesOrderCustomRepository as SOCR;
use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Entity;
use Cycle\Annotated\Annotation\Relation\BelongsTo;

#[Entity(repository: SOCR::class)]
class SalesOrderCustom
{
    use RequireId;

    #[Column(type: 'primary')]
    private ?int $id = null;

    #[BelongsTo(target: CustomField::class, nullable: false)]
    private ?CustomField $custom_field = null;

    #[BelongsTo(target: SalesOrder::class, nullable: false)]
    private ?SalesOrder $sales_order = null;

    public function __construct(
        #[Column(type: 'integer(11)', nullable: false)]
        private ?int $sales_order_id = null,

        #[Column(type: 'integer(11)', nullable: false)]
        private ?int $custom_field_id = null,

        #[Column(type: 'text', nullable: true)]
        private string $value = '',
    ) {
    }

    public function reqId(): int
    {
        return $this->requireId($this->id, 'SalesOrderCustom');
    }

    public function hasIdentity(): bool
    {
        return $this->id !== null;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function getCustomField(): ?CustomField
    {
        return $this->custom_field;
    }

    public function setCustomField(?CustomField $custom_field): void
    {
        $this->custom_field = $custom_field;
    }

    public function getSalesOrder(): ?SalesOrder
    {
        return $this->sales_order;
    }

    public function setSalesOrder(?SalesOrder $sales_order): void
    {
        $this->sales_order = $sales_order;
    }

    public function reqSalesOrderId(): int
    {
        return $this->requireId($this->sales_order_id, 'SalesOrder');
    }

    public function setSalesOrderId(int $sales_order_id): void
    {
        $this->sales_order_id = $sales_order_id;
    }

    public function reqCustomFieldId(): int
    {
        return $this->requireId($this->custom_field_id, 'Custom Field');
    }

    public function setCustomFieldId(int $custom_field_id): void
    {
        $this->custom_field_id = $custom_field_id;
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function setValue(string $value): void
    {
        $this->value = $value;
    }
}
