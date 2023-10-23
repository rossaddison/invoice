<?php
declare(strict_types=1); 

namespace App\Invoice\Entity;

use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Entity;
use Cycle\Annotated\Annotation\Relation\BelongsTo;
use \DateTime;
use \DateTimeImmutable;
use App\Invoice\Entity\TaxRate;
use App\Invoice\Entity\Product;
use App\Invoice\Entity\SalesOrder;

#[Entity(repository: \App\Invoice\SalesOrderItem\SalesOrderItemRepository::class)]
class SalesOrderItem
{   
    #[Column(type: 'primary')]
    public ?int $id =  null;
    
    // The client/customer is required to match this item with their purchase order item number
    // This value will be input by the client/customer from their side
    // https://docs.peppol.eu/poacc/billing/3.0/syntax/ubl-invoice/cac-InvoiceLine/cac-Item/cac-BuyersItemIdentification/cbc-ID/
    #[Column(type: 'text', nullable: true)]
    private ?string $peppol_po_itemid =  '';
    
    // https://docs.peppol.eu/poacc/billing/3.0/syntax/ubl-invoice/cac-InvoiceLine/cac-OrderLineReference/cbc-LineID/
    #[Column(type: 'text', nullable: true)]
    private ?string $peppol_po_lineid =  '';
    
    #[Column(type: 'date', nullable: false)]
    private mixed $date_added;
     
    #[Column(type: 'text', nullable: true)]
    private ?string $name =  '';
     
    #[Column(type: 'text', nullable: true)]
    private ?string $description =  '';
    
    #[Column(type: 'decimal(20,2)', nullable: false, default: 1.00)]
    private ?float $quantity = 1.00;
     
    #[Column(type: 'decimal(20,2)', nullable: false, default: 0.00)]
    private ?float $price =  0.00;
     
    #[Column(type: 'decimal(20,2)', nullable: false, default: 0.00)]
    private ?float $discount_amount = 0.00;
    
    #[Column(type: 'decimal(20,2)', nullable: false, default: 0.00)]
    private ?float $charge_amount = 0.00;
    
    #[Column(type: 'integer(2)', nullable: false, default:0)]
    private ?int $order =  null;
    
    #[Column(type: 'string(50)', nullable: true)]
    private ?string $product_unit =  '';
    
    #[BelongsTo(target:SalesOrder::class, nullable: false, fkAction: 'NO ACTION')]
    private ?SalesOrder $sales_order = null; 
    #[Column(type: 'integer(11)', nullable: false)]        
    private ?int $sales_order_id =  null;   
        
    #[BelongsTo(target:TaxRate::class, nullable: false, fkAction: "NO ACTION")]
    private ?TaxRate $tax_rate = null;    
    #[Column(type: 'integer(11)', nullable: false)]
    private ?int $tax_rate_id = null;
    
    // Mandatory: The item MUST have a product however psalm testing requires it to be in the constructor => nullable
    #[BelongsTo(target:Product::class, nullable: false, fkAction: 'NO ACTION')]
    private ?Product $product = null;
    #[Column(type: 'integer(11)', nullable: true)]
    private ?int $product_id;
    
    #[Column(type: 'integer(11)', nullable: true)]
    private ?int $product_unit_id;
     
    public function __construct(
        int $id = null,
        string $peppol_po_itemid = '',
        string $peppol_po_lineid = '',
        string $name = '',
        string $description = '',
        float $quantity = 1.00,
        float $price = 0.00,
        float $discount_amount = 0.00,
        float $charge_amount = 0.00,    
        // the relative order of the item on the invoice.  
        int $order = null,
        string $product_unit = '',
        int $sales_order_id = null,
        int $tax_rate_id = null,
        int $product_id = null,
        int $product_unit_id = null
    )
    {
        $this->id=$id;
        $this->peppol_po_itemid=$peppol_po_itemid;
        $this->peppol_po_lineid=$peppol_po_lineid;
        $this->date_added= new \DateTimeImmutable();
        $this->name=$name;
        $this->description=$description;
        $this->quantity=$quantity;
        $this->price=$price;
        $this->discount_amount=$discount_amount;
        $this->charge_amount=$charge_amount;
        $this->order=$order;
        $this->product_unit=$product_unit;
        $this->sales_order_id=$sales_order_id;
        $this->tax_rate_id=$tax_rate_id;
        $this->product_id=$product_id;
        $this->product_unit_id=$product_unit_id;
    }
    
    //relation $tax_rate
    public function getTaxRate(): ?TaxRate
    {
        return $this->tax_rate;
    }
    
    //set relation $taxrate
    public function setTaxRate(?TaxRate $taxrate): void
    {
        $this->tax_rate = $taxrate;
    }
        
    public function getProduct() : Product|null
    {
      return $this->product;
    }
    
    //set relation $product
    public function setProduct(?Product $product): void
    {
        $this->product = $product;
    }    
    
    public function getSalesOrder() : SalesOrder|null
    {
      return $this->sales_order;
    }
    
    public function setSalesOrder(?SalesOrder $sales_order): void
    {
        $this->sales_order = $sales_order;
    }
    
    public function getId(): string
    {
     return (string)$this->id;
    }
    
    public function setId(int $id) : void
    {
      $this->id = $id;
    }
    
    public function getSales_order_id(): string
    {
     return (string)$this->sales_order_id;
    }
    
    public function setSales_order_id(int $sales_order_id) : void
    {
      $this->sales_order_id =  $sales_order_id;
    }
    
    public function getPeppol_po_itemid(): ?string
    {
       return $this->peppol_po_itemid;
    }
    
    public function setPeppol_po_itemid(string $peppol_po_itemid) : void
    {
       $this->peppol_po_itemid =  $peppol_po_itemid;
    }
    
    public function getPeppol_po_lineid(): ?string
    {
       return $this->peppol_po_lineid;
    }
    
    public function setPeppol_po_lineid(string $peppol_po_lineid) : void
    {
       $this->peppol_po_lineid =  $peppol_po_lineid;
    }
    
    public function getTax_rate_id(): string
    {
     return (string)$this->tax_rate_id;
    }
    
    public function setTax_rate_id(int $tax_rate_id) : void
    {
      $this->tax_rate_id =  $tax_rate_id;
    }
    
    public function getProduct_id(): string
    {
     return (string)$this->product_id;
    }
    
    public function setProduct_id(int $product_id) : void
    {
      $this->product_id =  $product_id;
    }
    
    public function getDate_added(): DateTimeImmutable
    {
      /** @var DateTimeImmutable $this->date_added */
      return $this->date_added;
    }
    
    public function setDate_added(DateTime $date_added) : void
    {
      $this->date_added =  $date_added;
    }
    
    public function getName(): ?string
    {
       return $this->name;
    }
    
    public function setName(string $name) : void
    {
      $this->name =  $name;
    }
    
    public function getDescription(): ?string
    {
       return $this->description;
    }
    
    public function setDescription(string $description) : void
    {
      $this->description =  $description;
    }
    
    public function getQuantity(): ?float
    {
       return $this->quantity;
    }
    
    public function setQuantity(float $quantity) : void
    {
      $this->quantity =  $quantity;
    }
    
    public function getPrice(): ?float
    {
       return $this->price;
    }
    
    public function setPrice(float $price) : void
    {
      $this->price =  $price;
    }
    
    public function getDiscount_amount(): ?float
    {
       return $this->discount_amount;
    }
    
    public function setDiscount_amount(float $discount_amount) : void
    {
      $this->discount_amount =  $discount_amount;
    }
    
    public function getCharge_amount(): ?float
    {
       return $this->charge_amount;
    }
    
    public function setCharge_amount(float $charge_amount) : void
    {
      $this->charge_amount =  $charge_amount;
    }
    
    public function getOrder(): int|null
    {
       return $this->order;
    }
    
    public function setOrder(int $order) : void
    {
      $this->order =  $order;
    }
    
    public function getProduct_unit(): ?string
    {
       return $this->product_unit;
    }
    
    public function setProduct_unit(string $product_unit) : void
    {
      $this->product_unit =  $product_unit;
    }
    
    public function getProduct_unit_id(): string
    {
     return (string)$this->product_unit_id;
    }
    
    public function setProduct_unit_id(int $product_unit_id) : void
    {
      $this->product_unit_id =  $product_unit_id;
    }
}