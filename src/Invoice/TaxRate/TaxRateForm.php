<?php

declare(strict_types=1);

namespace App\Invoice\TaxRate;

use App\Invoice\Entity\TaxRate;
use Yiisoft\FormModel\FormModel;
use Yiisoft\Validator\Rule\Required;

final class TaxRateForm extends FormModel
{
    #[Required]
    private ?string $tax_rate_name = '';

    #[Required]
    private ?float $tax_rate_percent = 0.00;

    private ?bool $tax_rate_default = false;

    private ?string $tax_rate_code = '';

    private ?string $peppol_tax_rate_code = '';

    private ?string $storecove_tax_type = '';

    public function __construct(TaxRate $taxRate)
    {
        $this->tax_rate_name        = $taxRate->getTaxRateName();
        $this->tax_rate_percent     = $taxRate->getTaxRatePercent();
        $this->tax_rate_default     = $taxRate->getTaxRateDefault();
        $this->tax_rate_code        = $taxRate->getTaxRateCode();
        $this->peppol_tax_rate_code = $taxRate->getPeppolTaxRateCode();
        $this->storecove_tax_type   = $taxRate->getStorecoveTaxType();
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
     * @psalm-return ''
     */
    #[\Override]
    public function getFormName(): string
    {
        return '';
    }
}
