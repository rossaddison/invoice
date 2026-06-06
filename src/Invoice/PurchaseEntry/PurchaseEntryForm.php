<?php

declare(strict_types=1);

namespace App\Invoice\PurchaseEntry;

use App\Infrastructure\Persistence\PurchaseEntry\PurchaseEntry;
use Yiisoft\FormModel\FormModel;
use Yiisoft\Validator\Rule\Length;
use Yiisoft\Validator\Rule\Number;
use Yiisoft\Validator\Rule\Required;

final class PurchaseEntryForm extends FormModel
{
    #[Required]
    private ?string $date = '';

    #[Required]
    #[Length(min: 1, max: 200)]
    private ?string $supplier = '';

    #[Length(min: 0, max: 500, skipOnEmpty: true)]
    private ?string $description = null;

    #[Required]
    #[Number(min: 0)]
    private ?float $amount_ex_vat = 0.00;

    #[Required]
    #[Number(min: 0)]
    private ?float $vat_amount = 0.00;

    public static function show(PurchaseEntry $entry): self
    {
        $form = new self();
        $form->date = $entry->getDate();
        $form->supplier = $entry->getSupplier();
        $form->description = $entry->getDescription();
        $form->amount_ex_vat = $entry->getAmountExVat();
        $form->vat_amount = $entry->getVatAmount();
        return $form;
    }

    public function getDate(): ?string
    {
        return $this->date;
    }

    public function getSupplier(): ?string
    {
        return $this->supplier;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getAmountExVat(): ?float
    {
        return $this->amount_ex_vat;
    }

    public function getVatAmount(): ?float
    {
        return $this->vat_amount;
    }

    #[\Override]
    public function getFormName(): string
    {
        return '';
    }
}
