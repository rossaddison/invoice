<?php

declare(strict_types=1);

namespace App\Invoice\InvAllowanceCharge;

use App\Infrastructure\Persistence\{
    InvAllowanceCharge\InvAllowanceCharge
};
use Yiisoft\Validator\Rule\GreaterThan;
use Yiisoft\FormModel\FormModel;

final class InvAllowanceChargeForm extends FormModel
{
    private ?int $allowance_charge_id = null;

    #[GreaterThan(0)]
    private ?float $amount = null;

    private ?float $vat_or_tax = null;
    
    private ?int $inv_id = null;

    public static function show(
        InvAllowanceCharge $invAllowanceCharge,
        ?int $inv_id): self
    {
        $form = new self();
        $form->allowance_charge_id = $invAllowanceCharge->reqAllowanceChargeId();
        $form->amount = $invAllowanceCharge->getAmount();
        $form->vat_or_tax = $invAllowanceCharge->getVatOrTax();
        $form->inv_id = $inv_id;
        return $form;
    }

    public function getInvId(): ?int
    {
        return $this->inv_id;
    }

    public function getAllowanceChargeId(): ?int
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
