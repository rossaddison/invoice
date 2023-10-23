<?php
declare(strict_types=1);

namespace App\Invoice\Entity;

use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Entity;
use App\Invoice\Entity\Family;
use App\Invoice\Entity\TaxRate;
use App\Invoice\Entity\Unit;
use Cycle\Annotated\Annotation\Relation\BelongsTo;

#[Entity(repository: \App\Invoice\Product\ProductRepository::class)]
class Product
{
    #[Column(type: 'primary')]
    private ?int $id = null;
        
    #[Column(type: 'text', nullable: true)]
    private ?string $product_sku = '';
    
    // sii => Standard Item Identification 
    // eg. https://www.gtin.info/
    #[Column(type: 'text', nullable: true)]
    private ?string $product_sii_schemeid = '';
    
    // https://docs.peppol.eu/poacc/billing/3.0/syntax/ubl-invoice/cac-InvoiceLine/cac-Item/cac-StandardItemIdentification/cbc-ID/
    // eg. if gtin is being used specifiy the 14 digit code used for bar-codes
    #[Column(type: 'text', nullable: true)]
    private ?string $product_sii_id = '';
    
    // https://docs.peppol.eu/poacc/billing/3.0/syntax/ubl-invoice/cac-InvoiceLine/cac-Item/cac-CommodityClassification/cbc-ItemClassificationCode/
    // icc => Item Classification Code
    // eg. 'Id' => 'SRV', 
    // 'Name' => 'GS1 Global Trade Item Number', 
    // 'Description' => 'A unique number, up to 14-digits, assigned according to the numbering structure of the GS1 system.',
    // eg. 'SRV'     
    #[Column(type: 'text', nullable: true)]
    private ?string $product_icc_listid = '';
    
    #[Column(type: 'text', nullable: true)]
    private ?string $product_icc_listversionid = '';
    
    // https://docs.peppol.eu/poacc/billing/3.0/syntax/ubl-invoice/cac-InvoiceLine/cac-Item/cac-CommodityClassification/cbc-ItemClassificationCode/
    // eg. 9873242
    #[Column(type: 'text', nullable: true)]
    private ?string $product_icc_id = '';
    
    #[Column(type: 'string(2)', nullable: true)]
    private ?string $product_country_of_origin_code = '';
    
    #[Column(type: 'text', nullable: true)]
    private ?string $product_name = '';
    
    #[Column(type: 'longText', nullable: false)]
    private ?string $product_description = '';
            
    #[Column(type: 'decimal(20,2)', nullable: true)]
    private ?float $product_price = null;
    
    #[Column(type: 'integer(11)', nullable: false, default: 1)]
    private float $product_price_base_quantity = 1.00;
    
    #[Column(type: 'decimal(20,2)', nullable: true)]
    private ?float $purchase_price = null;
    
    #[Column(type: 'text', nullable: true)]
    private ?string $provider_name = '';
    
    #[Column(type: 'text', nullable: true)]
    private ?string $product_additional_item_property_name = '';
    
    #[Column(type: 'text', nullable: true)]
    private ?string $product_additional_item_property_value = '';
    
    #[BelongsTo(target:Family::class, nullable: false, fkAction: "NO ACTION")]
    private ?Family $family = null;        
    #[Column(type: 'integer(11)', nullable: true)]
    private ?int $family_id = null;
    
    // A product has to have a tax rate before it can be created even if it is a zero tax rate    
    #[BelongsTo(target:TaxRate::class, nullable: false, fkAction: "NO ACTION")]
    private ?TaxRate $tax_rate = null;    
    #[Column(type: 'integer(11)', nullable: false)]
    private ?int $tax_rate_id = null;
        
    #[BelongsTo(target:Unit::class, nullable: false, fkAction: "NO ACTION")]
    private ?Unit $unit = null;    
    #[Column(type: 'integer(11)', nullable: true)]
    private ?int $unit_id = null;
    
    #[Column(type: 'integer(11)', nullable: true)]
    private ?int $unit_peppol_id = null;
    
    #[Column(type: 'integer(11)', nullable: true)]
    private ?int $product_tariff = null;
    
    public function __construct(
        string $product_sku = '',
        string $product_sii_schemeid = '',
        string $product_sii_id = '',
        string $product_icc_listid = '',        
        string $product_icc_listversionid = '',    
        string $product_icc_id = '',    
        string $product_country_of_origin_code = '',
        string $product_name = '',
        string $product_description = '',
        float $product_price = 0.00,
        float $purchase_price = 0.00,
        float $product_price_base_quantity = 1.00,
        string $provider_name = '',
        int $product_tariff = null,
        string $product_additional_item_property_name = '',
        string $product_additional_item_property_value = '',
        int $tax_rate_id = null,
        int $unit_id = null,
        int $unit_peppol_id = null,
        int $family_id = null,           
    )
    {
        $this->product_sku = $product_sku;
        $this->product_sii_schemeid = $product_sii_schemeid;
        $this->product_sii_id = $product_sii_id;
        $this->product_icc_listid = $product_icc_listid;        
        $this->product_icc_listversionid = $product_icc_listversionid;
        $this->product_icc_id = $product_icc_id;
        $this->product_country_of_origin_code = $product_country_of_origin_code;
        $this->product_name = $product_name;
        $this->product_description = $product_description;
        $this->product_price = $product_price;
        // how many items the selling price includes eg. 1 crate has 10 bags of cement
        $this->product_price_base_quantity = $product_price_base_quantity;
        $this->purchase_price = $purchase_price;
        $this->provider_name = $provider_name;
        $this->product_tariff = $product_tariff;
        $this->product_additional_item_property_name = $product_additional_item_property_name;
        $this->product_additional_item_property_value = $product_additional_item_property_value;
        $this->tax_rate_id = $tax_rate_id;
        $this->unit_id = $unit_id;
        $this->unit_peppol_id = $unit_peppol_id;
        $this->family_id = $family_id;        
    }
    
    //get relation $family
    public function getFamily(): ?Family
    {
        return $this->family;
    }
    
    //set relation $family
    public function setFamily(?Family $family): void
    {
        $this->family = $family;
    }
    
    //relation $tax_rate
    public function getTaxRate(): ?TaxRate
    {
        return $this->tax_rate;
    }
    
    //set relation $taxrate
    public function setTaxrate(?TaxRate $taxrate): void
    {
        $this->tax_rate = $taxrate;
    }
    
    //relation $unit
    public function getUnit(): ?Unit
    {
        return $this->unit;
    }

    //set relation $unit
    public function setUnit(?Unit $unit): void
    {
        $this->unit = $unit;
    }
    
    public function getProduct_id(): string
    {
        return (string)$this->id;
    }
    
    public function getFamily_id(): string
    {
        return (string)$this->family_id;
    }
    
    public function setFamily_id(int $family_id): void
    {
        $this->family_id = $family_id;
    }
    
    public function getProduct_sku(): string|null
    {
        return $this->product_sku;
    }
    
    public function setProduct_sku(string $product_sku): void
    {
        $this->product_sku = $product_sku;
    }
    
    // https://docs.peppol.eu/poacc/billing/3.0/bis/#_item_identifiers
    // Standard Item Identification Code Default '0160'
    
    /**
     * Used with PeppolArrays getIso_6523_icd function
     * @return string|null
     */
    public function getProduct_sii_schemeid(): string|null
    {
        return $this->product_sii_schemeid;
    }
    
    /**
     * Mandatory (M) eg. 0160 from PeppolArrays getIso6523_icd()
     * @see https://docs.peppol.eu/poacc/billing/3.0/syntax/ubl-invoice/cac-InvoiceLine/cac-Item/cac-StandardItemIdentification/cbc-ID/
     * @param string $product_sii_schemeid
     * @return void
     */
    public function setProduct_sii_schemeid(string $product_sii_schemeid): void
    {
        $this->product_sii_schemeid = $product_sii_schemeid;
    }
    
    /**
     * @see https://docs.peppol.eu/poacc/billing/3.0/syntax/ubl-invoice/cac-InvoiceLine/cac-Item/cac-StandardItemIdentification/cbc-ID/
     * @return string|null
     */
    public function getProduct_sii_id(): string|null
    {
        return $this->product_sii_id;
    }
    
    public function setProduct_sii_id(string $product_sii_id): void
    {
        $this->product_sii_id = $product_sii_id;
    }
    
    /**
     * Used with src/Invoice/Helpers/Peppol/PeppolArrays function getUncl7143 eg. SRV 
     * @see https://docs.peppol.eu/poacc/billing/3.0/syntax/ubl-invoice/cac-InvoiceLine/cac-Item/cac-CommodityClassification/cbc-ItemClassificationCode/listID/
     * @return string|null
     */
    public function getProduct_icc_listid(): string|null
    {
        return $this->product_icc_listid;
    }
    
    public function setProduct_icc_listid(string $product_icc_listid): void
    {
        $this->product_icc_listid = $product_icc_listid;
    }
    
    public function getProduct_icc_listversionid(): string|null
    {
        return $this->product_icc_listversionid;
    }
    
    public function setProduct_icc_listversionid(string $product_icc_listversionid): void
    {
        $this->product_icc_listversionid = $product_icc_listversionid;
    }
    
    public function getProduct_icc_id(): string|null
    {
        return $this->product_icc_id;
    }
    
    public function setProduct_icc_id(string $product_icc_id): void
    {
        $this->product_icc_id = $product_icc_id;
    }
    
    public function setProduct_country_of_origin_code(string $product_country_of_origin_code): void
    {
        $this->product_country_of_origin_code = $product_country_of_origin_code;
    }
    
    public function getProduct_country_of_origin_code(): string|null
    {
        return $this->product_country_of_origin_code;
    }
    
    public function getProduct_name(): string|null
    {
        return $this->product_name;
    }
    
    public function setProduct_name(string $product_name): void
    {
        $this->product_name = $product_name;
    }
    
    public function getProduct_description(): string|null
    {
        return $this->product_description;
    }
    
    public function setProduct_description(string $product_description): void
    {
        $this->product_description = $product_description;
    }

    public function getProduct_price(): float|null
    {
        return $this->product_price;
    }
    
    public function setProduct_price(float $product_price): void
    {
        $this->product_price = $product_price;
    }
    
    public function getPurchase_price(): float|null
    {
        return $this->purchase_price;
    }
    
    public function getProduct_price_base_quantity() : float
    {
        return $this->product_price_base_quantity;
    }
    
    public function setProduct_price_base_quantity(float $product_price_base_quantity): void
    {
        $this->product_price_base_quantity = $product_price_base_quantity;
    }
        
    public function setPurchase_price(float $purchase_price): void
    {
        $this->purchase_price = $purchase_price;
    }
    
    public function getProvider_name(): string|null
    {
        return $this->provider_name;
    }
    
    public function setProvider_name(string $provider_name): void
    {
        $this->provider_name = $provider_name;
    }
    
    /**
     * eg. Colour
     * @see https://docs.peppol.eu/poacc/billing/3.0/syntax/ubl-invoice/cac-InvoiceLine/cac-Item/cac-AdditionalItemProperty/
     * @return string|null
     */
    public function getProduct_additional_item_property_name(): string|null
    {
        return $this->product_additional_item_property_name;
    }
    
    public function setProduct_additional_item_property_name(string $product_additional_item_property_name): void
    {
        $this->product_additional_item_property_name = $product_additional_item_property_name;
    }
    
    /**
     * eg. Black
     * @see https://docs.peppol.eu/poacc/billing/3.0/syntax/ubl-invoice/cac-InvoiceLine/cac-Item/cac-AdditionalItemProperty/
     * @return string|null
     */
    public function getProduct_additional_item_property_value(): string|null
    {
        return $this->product_additional_item_property_value;
    }
    
    public function setProduct_additional_item_property_value(string $product_additional_item_property_value): void
    {
        $this->product_additional_item_property_value = $product_additional_item_property_value;
    }

    public function setTax_rate_id(int $tax_rate_id): void
    {
        $this->tax_rate_id = $tax_rate_id;
    }

    public function getTax_rate_id(): string
    {
        return (string)$this->tax_rate_id;
    }

    public function setUnit_id(int $unit_id): void    
    {
        $this->unit_id = $unit_id;
    }

    public function getUnit_id(): string
    {
        return (string)$this->unit_id;
    }

    public function setUnit_peppol_id(int $unit_peppol_id): void    
    {
        $this->unit_peppol_id = $unit_peppol_id;
    }

    public function getUnit_peppol_id(): string
    {
        return (string)$this->unit_peppol_id;
    }   
    
    public function getProduct_tariff(): int|null
    {
        return $this->product_tariff;
    }

    public function setProduct_tariff(int $product_tariff): void
    {
        $this->product_tariff = $product_tariff;
    }
    
    /**
     * Make sure the sequence of parameters is correct 
     * @param int $tax_rate_id
     * @param int $unit_id
     * @param int $family_id
     * @return void
     */
    public function nullifyRelationOnChange(int $tax_rate_id, int $unit_id, int $family_id) : void {
        if ($this->tax_rate_id <> $tax_rate_id) {
           $this->tax_rate = null;
        }        
        if ($this->unit_id <> $unit_id) {
           $this->unit = null; 
        }
        if ($this->family_id <> $family_id) {
           $this->family = null;
        }
    }
    
    /**
     * @see https://github.com/yiisoft/demo/issues/462
     * Used in ProductService
     * @param int $tax_rate_id
     * @param int $unit_id
     * @param int $family_id
     * @return void
     */
    public function nullifyRelationOnChange_old_version(int $tax_rate_id, int $unit_id, int $family_id) : void {
        if (($this->getTaxrate()?->getTax_rate_id() == $tax_rate_id) && (!empty($tax_rate_id))) {
           $this->setTaxrate($this->getTaxrate());
        } else {
           $this->setTaxrate(null); 
        }       
        if (($this->getUnit()?->getUnit_id() == $unit_id) && (!empty($unit_id))) {
           $this->setUnit($this->getUnit()); 
        } else {
           $this->setUnit(null); 
        }
        if (($this->getFamily()?->getFamily_id() == $family_id) && (!empty($family_id))) {
           $this->setFamily($this->getFamily());
        } else {
           $this->setFamily(null); 
        }
    }
}