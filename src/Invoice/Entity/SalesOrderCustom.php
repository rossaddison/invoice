<?php

declare(strict_types=1);

namespace App\Invoice\Entity;

use App\Invoice\SalesOrderCustom\SalesOrderCustomRepository as SOCR;
use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Entity;
use Cycle\Annotated\Annotation\Relation\BelongsTo;

#[Entity(repository: SOCR::class)]
class SalesOrderCustom
{
    #[BelongsTo(target: CustomField::class, nullable: false)]
    private ?CustomField $custom_field = null;

    #[BelongsTo(target: SalesOrder::class, nullable: false)]
    private ?SalesOrder $sales_order = null;

    public function __construct(
        #[Column(type: 'primary')]
        private ?int $id = null,
        
        #[Column(type: 'integer(11)', nullable: false)]
        private ?int $sales_order_id = null,
            
        #[Column(type: 'integer(11)', nullable: false)]
        private ?int $custom_field_id = null,
            
        #[Column(type: 'text', nullable: true)]
        private string $value = '')
    {
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

    public function getId(): string
    {
        return (string) $this->id;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function getSales_order_id(): string
    {
        return (string) $this->sales_order_id;
    }

    public function setSales_order_id(int $sales_order_id): void
    {
        $this->sales_order_id = $sales_order_id;
    }

    public function getCustom_field_id(): string
    {
        return (string) $this->custom_field_id;
    }

    public function setCustom_field_id(int $custom_field_id): void
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
