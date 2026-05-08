<?php

declare(strict_types=1);

namespace App\Invoice\TaxRate;

use App\Infrastructure\Persistence\TaxRate\TaxRate;
use Yiisoft\FormModel\FormModel;
use Yiisoft\Validator\Rule\Length;
use Yiisoft\Validator\Rule\Required;

final class TaxRateForm extends FormModel
{
    #[Required]
    #[Length(min: 1, max: 100)]
    private ?string $tax_rate_name = '';

    #[Required]
    private ?float $tax_rate_percent = 0.00;

    private ?bool $tax_rate_default = false;

    #[Length(min: 0, max: 2, skipOnEmpty: true)]
    private ?string $tax_rate_code = '';

    #[Length(min: 0, max: 2, skipOnEmpty: true)]
    private ?string $peppol_tax_rate_code = '';

    #[Length(min: 0, max: 30, skipOnEmpty: true)]
    private ?string $storecove_tax_type = '';

    public static function show(TaxRate $taxRate): self
    {
        $form = new self();
        $form->tax_rate_name = $taxRate->getTaxRateName();
        $form->tax_rate_percent = $taxRate->getTaxRatePercent();
        $form->tax_rate_default = $taxRate->getTaxRateDefault();
        $form->tax_rate_code = $taxRate->getTaxRateCode();
        $form->peppol_tax_rate_code = $taxRate->getPeppolTaxRateCode();
        $form->storecove_tax_type = $taxRate->getStorecoveTaxType();
        return $form;
    }

    public function getTaxRateName(): ?string
    {
        return $this->tax_rate_name;
    }

    public function getTaxRatePercent(): ?float
    {
        return $this->tax_rate_percent;
    }

    public function getTaxRateDefault(): ?bool
    {
        return $this->tax_rate_default;
    }

    public function getTaxRateCode(): ?string
    {
        return $this->tax_rate_code;
    }

    public function getPeppolTaxRateCode(): ?string
    {
        return $this->peppol_tax_rate_code;
    }

    public function getStorecoveTaxType(): ?string
    {
        return $this->storecove_tax_type;
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
