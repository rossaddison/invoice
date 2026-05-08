<?php

declare(strict_types=1);

namespace App\Invoice\SalesOrderCustom;

use App\Infrastructure\Persistence\SalesOrderCustom\SalesOrderCustom;
use Yiisoft\FormModel\FormModel;
use Yiisoft\Validator\Rule\Required;

final class SalesOrderCustomForm extends FormModel
{
    private ?int $sales_order_id = null;
    private ?int $custom_field_id = null;

    #[Required]
    private ?string $value = '';

    public static function show(SalesOrderCustom $salesOrderCustom): self
    {
        $form = new self();
        $form->sales_order_id = $salesOrderCustom->reqSalesOrderId();
        $form->custom_field_id = $salesOrderCustom->reqCustomFieldId();
        $form->value = $salesOrderCustom->getValue();
        return $form;
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
