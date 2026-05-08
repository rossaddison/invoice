<?php

declare(strict_types=1);

namespace App\Invoice\SalesOrderTaxRate;

use App\Infrastructure\Persistence\SalesOrderTaxRate\SalesOrderTaxRate;
use Yiisoft\FormModel\FormModel;
use Yiisoft\Validator\Rule\Required;

final class SalesOrderTaxRateForm extends FormModel
{
    private ?int $sales_order_id = null;

    #[Required]
    private ?int $tax_rate_id = null;

    private ?int $include_item_tax = null;
    private ?float $sales_order_tax_rate_amount = null;

    public static function show(SalesOrderTaxRate $salesOrderTaxRate): self
    {
        $form = new self();
        $form->sales_order_id = $salesOrderTaxRate->reqSalesOrderId();
        $form->tax_rate_id = $salesOrderTaxRate->reqTaxRateId();
        $form->include_item_tax = $salesOrderTaxRate->getIncludeItemTax();
        $form->sales_order_tax_rate_amount = $salesOrderTaxRate->getSalesOrderTaxRateAmount();
        return $form;
    }

    public function getSalesOrderId(): ?int
    {
        return $this->sales_order_id;
    }

    public function getTaxRateId(): ?int
    {
        return $this->tax_rate_id;
    }

    public function getIncludeItemTax(): ?int
    {
        return $this->include_item_tax;
    }

    public function getSalesOrderTaxRateAmount(): float
    {
        return $this->sales_order_tax_rate_amount ?? 0.00;
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
