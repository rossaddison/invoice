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
        $this->tax_rate_name = $taxRate->getTax_rate_name();
        $this->tax_rate_percent = $taxRate->getTax_rate_percent();
        $this->tax_rate_default = $taxRate->getTax_rate_default();
        $this->tax_rate_code = $taxRate->getTax_rate_code();
        $this->peppol_tax_rate_code = $taxRate->getPeppol_tax_rate_code();
        $this->storecove_tax_type = $taxRate->getStorecove_tax_type();
    }        
    
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
    
    public function getTax_rate_code(): string|null
    {
        return $this->tax_rate_code;
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
