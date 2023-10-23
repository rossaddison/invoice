<?php
declare(strict_types=1);

namespace App\Invoice\Entity;

use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Entity;

#[Entity(repository: \App\Invoice\TaxRate\TaxRateRepository::class)]
class TaxRate
{
    #[Column(type: 'primary')]
    private ?int $id = null;
    
    #[Column(type: 'text', nullable: true)]
    private ?string $tax_rate_name = null;
    
    #[Column(type: 'string(2)', nullable: true)]
    private ?string $tax_rate_code = null;
    
    #[Column(type: 'string(2)', nullable: true)]
    private ?string $peppol_tax_rate_code = null;
    
    #[Column(type: 'string(30)', nullable: false, default: 'standard')]
    private string $storecove_tax_type;
    
    #[Column(type: 'decimal(5,2)', nullable: false, default_value: 0.00 )]
    private ?float $tax_rate_percent = null;
    
    #[Column(type: 'bool', default:false)]
    private bool $tax_rate_default = false;
    
    public function __construct(
        string $tax_rate_code='',
        string $peppol_tax_rate_code='',
        string $storecove_tax_type='',
        string $tax_rate_name='',
        float $tax_rate_percent=0.00,
        bool $tax_rate_default=false
    )
    {
        $this->tax_rate_code = $tax_rate_code;
        $this->peppol_tax_rate_code = $peppol_tax_rate_code;
        $this->storecove_tax_type = $storecove_tax_type;
        $this->tax_rate_name = $tax_rate_name;
        $this->tax_rate_percent = $tax_rate_percent;
        $this->tax_rate_default = $tax_rate_default;
    }
    
    public function setTax_rate_id(int $tax_rate_id): void
    {
        $this->id = $tax_rate_id;
    }
    
    public function getTax_rate_id(): ?int
    {
        return $this->id;
    }

    public function getTax_rate_name(): ?string
    {
        return $this->tax_rate_name;
    }

    public function setTax_rate_name(string $tax_rate_name): void
    {
        $this->tax_rate_name = $tax_rate_name;
    }
    
    public function getTax_rate_code(): ?string
    {
        return $this->tax_rate_code;
    }

    public function setTax_rate_code(string $tax_rate_code): void
    {
        $this->tax_rate_code = $tax_rate_code;
    }
    
    public function getPeppol_tax_rate_code(): ?string
    {
        return $this->peppol_tax_rate_code;
    }

    public function setPeppol_tax_rate_code(string $peppol_tax_rate_code): void
    {
        $this->peppol_tax_rate_code = $peppol_tax_rate_code;
    }
    
    public function getStorecove_tax_type() : string  
    {
        return $this->storecove_tax_type;
    }
    
    public function setStorecove_tax_type(string $storecove_tax_type): void
    {
        $this->storecove_tax_type = $storecove_tax_type;
    }  
        
    public function getTax_rate_percent(): ?float
    {
        return $this->tax_rate_percent;
    }
    
    public function setTax_rate_percent(float $tax_rate_percent): void
    {
        $this->tax_rate_percent = $tax_rate_percent; 
    }
    
    public function getTax_rate_default(): bool
    {
        return $this->tax_rate_default;
    }
    
    public function setTax_rate_default(bool $tax_rate_default): void 
    {
        $this->tax_rate_default = $tax_rate_default;
    }
    
    public function isNewRecord(): bool
    {
        return $this->getTax_rate_id() === null;
    }
}
