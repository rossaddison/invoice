<?php

declare(strict_types=1);

namespace App\Invoice\TaxRate;

use Yiisoft\Form\FormModel;

final class TaxRateForm extends FormModel
{
    private ?string $tax_rate_name = '';
    
    private ?float $tax_rate_percent = 0.00;
    
    private ?bool $tax_rate_default = false;
        
    private ?string $peppol_tax_rate_code = '';
    
    private ?string $storecove_tax_type = '';
    
    public function getTax_rate_name(): string|null
    {
        return $this->tax_rate_name;
    }
    
    public function getTax_rate_percent() : float|null
    {
        return $this->tax_rate_percent;
    }
    
    public function getTax_rate_default() : bool|null
    {
        return $this->tax_rate_default;
    }
    
    public function getPeppol_tax_rate_code(): string|null
    {
        return $this->peppol_tax_rate_code;
    }
    
    public function getStorecove_tax_type(): string|null
    {
        return $this->storecove_tax_type;
    }
    
    /**
     * @return string
     *
     * @psalm-return ''
     */
    public function getFormName(): string
    {
        return '';
    }
}
