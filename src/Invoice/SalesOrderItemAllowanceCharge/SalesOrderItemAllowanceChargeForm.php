<?php

declare(strict_types=1);

namespace App\Invoice\SalesOrderItemAllowanceCharge;

use App\Invoice\Entity\SalesOrderItemAllowanceCharge;
use Yiisoft\FormModel\FormModel;
use Yiisoft\Validator\Rule\Required;

final class SalesOrderItemAllowanceChargeForm extends FormModel
{
    private ?int $sales_order_id = null;
    #[Required]
    private ?int $allowance_charge_id = null;
    #[Required]
    private ?float $amount = null;
    #[Required]
    private ?float $vat_or_tax = null;

    public function __construct(
        SalesOrderItemAllowanceCharge $salesorderItemAllowanceCharge,
        private readonly ?int $sales_order_item_id)
    {
        $this->sales_order_id =
            (int) $salesorderItemAllowanceCharge->getSales_order_id();
        $this->allowance_charge_id =
            (int) $salesorderItemAllowanceCharge->getAllowance_charge_id();
        $this->amount = (float) $salesorderItemAllowanceCharge->getAmount();
        $this->vat_or_tax = (float) $salesorderItemAllowanceCharge->getVatOrTax();
    }

    public function getSales_order_id(): ?int
    {
        return $this->sales_order_id;
    }

    public function getSales_order_item_id(): ?int
    {
        return $this->sales_order_item_id;
    }

    public function getAllowance_charge_id(): ?int
    {
        return $this->allowance_charge_id;
    }

    public function getAmount(): ?float
    {
        return $this->amount;
    }

    public function getVatOrTax(): ?float
    {
        return $this->vat_or_tax;
    }

    /**
     * @return string
     * @psalm-return ''
     */
    #[\Override]
    public function getFormName(): string
    {
        return '';
    }
}
