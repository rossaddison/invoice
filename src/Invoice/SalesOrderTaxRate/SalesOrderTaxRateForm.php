<?php

declare(strict_types=1);

namespace App\Invoice\SalesOrderTaxRate;

use App\Invoice\Entity\SalesOrderTaxRate;
use Yiisoft\FormModel\FormModel;
use Yiisoft\Validator\Rule\Required;

final class SalesOrderTaxRateForm extends FormModel
{
    private ?int $sales_order_id = null;

    #[Required]
    private ?int $tax_rate_id = null;

    private ?int $include_item_tax = null;
    private ?float $sales_order_tax_rate_amount = null;

    public function __construct(SalesOrderTaxRate $salesOrderTaxRate)
    {
        $this->sales_order_id = (int) $salesOrderTaxRate->getSales_order_id();
        $this->tax_rate_id = (int) $salesOrderTaxRate->getTax_rate_id();
        $this->include_item_tax = $salesOrderTaxRate->getInclude_item_tax();
        $this->sales_order_tax_rate_amount = $salesOrderTaxRate->getSales_order_tax_rate_amount();
    }

    public function getSales_order_id(): ?int
    {
        return $this->sales_order_id;
    }

    public function getTax_rate_id(): ?int
    {
        return $this->tax_rate_id;
    }

    public function getInclude_item_tax(): ?int
    {
        return $this->include_item_tax;
    }

    public function getSales_order_tax_rate_amount(): float
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
