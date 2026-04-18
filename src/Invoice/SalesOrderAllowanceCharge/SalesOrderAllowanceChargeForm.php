<?php

declare(strict_types=1);

namespace App\Invoice\SalesOrderAllowanceCharge;

use App\Infrastructure\Persistence\SalesOrderAllowanceCharge\{
    SalesOrderAllowanceCharge,
};
use Yiisoft\Validator\Rule\GreaterThan;
use Yiisoft\FormModel\FormModel;

final class SalesOrderAllowanceChargeForm extends FormModel
{
    private ?int $allowance_charge_id = null;

    #[GreaterThan(0)]
    private ?int $amount = null;

    private ?int $vat_or_tax = null;

    public function __construct(
        SalesOrderAllowanceCharge $salesorderAllowanceCharge,
            private readonly ?int $salesorder_id)
    {
        $this->allowance_charge_id =
                (int) $salesorderAllowanceCharge->getAllowanceChargeId();
        $this->amount = (int) $salesorderAllowanceCharge->getAmount();
        $this->vat_or_tax = (int) $salesorderAllowanceCharge->getVatOrTax();
    }

    public function getSalesorderId(): ?int
    {
        return $this->salesorder_id;
    }

    public function getAllowanceChargeId(): ?int
    {
        return $this->allowance_charge_id;
    }

    public function getAmount(): ?int
    {
        return $this->amount;
    }

    public function getVatOrTax(): ?int
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
