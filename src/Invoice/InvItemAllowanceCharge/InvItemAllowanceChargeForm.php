<?php

declare(strict_types=1);

namespace App\Invoice\InvItemAllowanceCharge;

use App\Infrastructure\Persistence\{
    InvItemAllowanceCharge\InvItemAllowanceCharge
};
use Yiisoft\FormModel\FormModel;
use Yiisoft\Validator\Rule\Required;

final class InvItemAllowanceChargeForm extends FormModel
{
    private ?int $inv_id = null;
    #[Required]
    private ?int $allowance_charge_id = null;
    #[Required]
    private ?float $amount = null;
    private ?float $vat_or_tax = null;
    private ?int $inv_item_id = null;

    public static function show(InvItemAllowanceCharge $invItemAllowanceCharge,
        ?int $inv_item_id): self
    {
        $form = new self();
        $form->inv_id = $invItemAllowanceCharge->reqInvId();
        $form->allowance_charge_id = $invItemAllowanceCharge->reqAllowanceChargeId();
        $form->amount = (float) $invItemAllowanceCharge->getAmount();
        $form->vat_or_tax = (float) $invItemAllowanceCharge->getVatOrTax();
        $form->inv_item_id = $inv_item_id;
        return $form;
    }

    public function getInvId(): ?int
    {
        return $this->inv_id;
    }

    public function getInvItemId(): ?int
    {
        return $this->inv_item_id;
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
