<?php

declare(strict_types=1);

namespace App\Invoice\SalesOrderAllowanceCharge;

use App\Invoice\Entity\SalesOrderAllowanceCharge;
use Yiisoft\Validator\Rule\GreaterThan;
use Yiisoft\FormModel\FormModel;

final class SalesOrderAllowanceChargeForm extends FormModel
{
    private ?int $id = null;

    private ?int $allowance_charge_id = null;

    #[GreaterThan(0)]
    private ?int $amount = null;

    private ?int $vat_or_tax = null;

    public function __construct(
        SalesOrderAllowanceCharge $salesorderAllowanceCharge,
            private readonly ?int $salesorder_id)
    {
        $this->allowance_charge_id =
                (int) $salesorderAllowanceCharge->getAllowance_charge_id();
        $this->amount = (int) $salesorderAllowanceCharge->getAmount();
        $this->vat_or_tax = (int) $salesorderAllowanceCharge->getVatOrTax();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSalesorder_id(): ?int
    {
        return $this->salesorder_id;
    }

    public function getAllowance_charge_id(): ?int
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
