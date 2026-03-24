<?php

declare(strict_types=1);

namespace App\Invoice\SalesOrderCustom;

use App\Invoice\Entity\SalesOrderCustom;
use Yiisoft\FormModel\FormModel;
use Yiisoft\Validator\Rule\Required;

final class SalesOrderCustomForm extends FormModel
{
    private ?int $sales_order_id = null;
    private ?int $custom_field_id = null;

    #[Required]
    private ?string $value = '';

    public function __construct(SalesOrderCustom $salesOrderCustom)
    {
        $this->sales_order_id = (int) $salesOrderCustom->getSalesOrderId();
        $this->custom_field_id = (int) $salesOrderCustom->getCustomFieldId();
        $this->value = $salesOrderCustom->getValue();
    }

    public function getSalesOrderId(): ?int
    {
        return $this->sales_order_id;
    }

    public function getCustomFieldId(): ?int
    {
        return $this->custom_field_id;
    }

    public function getValue(): ?string
    {
        return $this->value;
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
