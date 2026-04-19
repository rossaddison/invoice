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

    private ?int $salesorder_id = null;
    
    #[GreaterThan(0)]
    private ?int $amount = null;

    private ?int $vat_or_tax = null;

    public static function show(
        SalesOrderAllowanceCharge $salesorderAllowanceCharge,
        ?int $salesorder_id): self
    {
        $form = new self();
        $form->allowance_charge_id =
                (int) $salesorderAllowanceCharge->getAllowanceChargeId();
        $form->amount = (int) $salesorderAllowanceCharge->getAmount();
        $form->salesorder_id = $salesorder_id;
        $form->vat_or_tax = (int) $salesorderAllowanceCharge->getVatOrTax();
        return $form;
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
