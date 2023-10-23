<?php
declare(strict_types=1); 

namespace App\Invoice\Entity;

use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Entity;
use App\Invoice\Entity\Inv;
use App\Invoice\Entity\TaxRate;
use App\Invoice\Entity\Product;
use App\Invoice\Entity\Task;
use Cycle\Annotated\Annotation\Relation\BelongsTo;
use \DateTime;
use \DateTimeImmutable;
  
#[Entity(repository: \App\Invoice\InvItem\InvItemRepository::class)]
class InvItem
{    
    #[Column(type: 'primary')]
    public ?int $id =  null;
     
    #[Column(type: 'integer(11)', nullable: false)]
    private ?int $inv_id =  null;
    
    #[Column(type: 'integer(11)', nullable: true)]
    private ?int $so_item_id =  null;
    
    #[Column(type: 'date', nullable: false)]
    private mixed $date_added;
    
    #[Column(type: 'text', nullable: true)]
    private ?string $name =  '';
     
    #[Column(type: 'longText', nullable: true)]
    private ?string $description =  '';
    
    #[Column(type: 'decimal(10,2)', nullable: false, default: 1)]
    private ?float $quantity =  null;
     
    #[Column(type: 'decimal(20,2)', nullable: false, default: 0.00)]
    private ?float $price =  0.00;
     
    #[Column(type: 'decimal(20,2)', nullable: false, default: 0.00)]
    private ?float $discount_amount =  0.00;
    
    #[Column(type: 'integer(2)', nullable: true, default:0)]
    private ?int $order =  null;
     
    #[Column(type: 'boolean', nullable: false)]
    private ?bool $is_recurring =  false;
     
    #[Column(type: 'string(50)', nullable: true)]
    private ?string $product_unit =  '';
    
    #[Column(type: 'integer(11)', nullable: true)]
    private ?int $product_unit_id =  null;
    
    #[Column(type: 'datetime', nullable: true)]
    private DateTimeImmutable $date;
    
    #[BelongsTo(target: \App\Invoice\Entity\TaxRate::class, nullable: false, fkAction: 'NO ACTION')]
    private ?TaxRate $tax_rate = null;
    #[Column(type: 'integer(11)', nullable: false, default:0)]
    private ?int $tax_rate_id =  null;
    
    #[BelongsTo(target: \App\Invoice\Entity\Product::class, nullable: true, fkAction: 'NO ACTION')]
    private ?Product $product = null;
    #[Column(type: 'integer(11)', nullable: true, default:null)]
    private ?int $product_id =  null;
    
    #[BelongsTo(target: \App\Invoice\Entity\Task::class, nullable: true, fkAction: 'NO ACTION')]
    private ?Task $task = null;
    #[Column(type: 'integer(11)', nullable: true, default:null)]
    private ?int $task_id =  null;
     
    #[BelongsTo(target: \App\Invoice\Entity\Inv::class, nullable: false, fkAction: 'NO ACTION')]
    private ?Inv $inv = null; 
    
    #[Column(type: 'integer(2)', nullable: true, default:0)]
    private ?int $belongs_to_vat_invoice =  null;

    #[Column(type: 'integer(11)', nullable: true)]
    private ?int $delivery_id =  null;

    #[Column(type: 'longText', nullable: true)]
    private ?string $note =  null;
    
    public function __construct(
        int $id = null,
        string $name = '',
        string $description = '',
        float $quantity = null,
        float $price = null,
        float $discount_amount = null,  
        int $order = null,
        bool $is_recurring = false,
        string $product_unit = '',
        int $inv_id = null,
        int $so_item_id = null,    
        int $tax_rate_id = null,
        int $product_id = null,
        int $task_id = null,
        int $product_unit_id = null,            
        int $belongs_to_vat_invoice = null,
        int $delivery_id = null,
        string $note = null,
    )
    {
        $this->id=$id;
        $this->date_added=new \DateTimeImmutable();
        $this->task_id=$task_id;
        $this->name=$name;
        $this->description=$description;
        $this->quantity=$quantity;
        $this->price=$price;
        $this->discount_amount=$discount_amount;
        $this->order=$order;
        $this->is_recurring=$is_recurring;
        $this->product_unit=$product_unit;
        $this->inv_id=$inv_id;
        $this->so_item_id=$so_item_id;
        $this->tax_rate_id=$tax_rate_id;
        $this->product_id=$product_id;
        $this->product_unit_id=$product_unit_id;
        $this->date=new \DateTimeImmutable();
        $this->belongs_to_vat_invoice=$belongs_to_vat_invoice;
        $this->delivery_id=$delivery_id;
        $this->note=$note;
    }
     
    public function getId(): int|null
    {
     return $this->id;
    }
    
    public function setId(int $id) : void
    {
      $this->id =  $id;
    }
    
    public function getTaxRate() : TaxRate|null {
      return $this->tax_rate;
    }
    
    //set relation $taxrate
    public function setTaxRate(?TaxRate $taxrate): void
    {
        $this->tax_rate = $taxrate;
    }
    
    public function getProduct() : ?Product {
      return $this->product;
    }
    
    //set relation $product
    public function setProduct(?Product $product): void
    {
        $this->product = $product;
    } 
    
    public function getTask() : ?Task {
      return $this->task;
    }
    
    //set relation $task
    public function setTask(?Task $task): void
    {
        $this->task = $task;
    } 
    
    public function getInv() : Inv|null {
      return $this->inv;
    }
    
    public function setInv(?Inv $inv): void
    {
        $this->inv = $inv;
    }
    
    public function getInv_id(): string
    {
     return (string)$this->inv_id;
    }
    
    public function setInv_id(int $inv_id) : void
    {
      $this->inv_id = $inv_id;
    }
    
    public function getSo_item_id(): string
    {
     return (string)$this->so_item_id;
    }
    
    public function setSo_item_id(int $so_item_id) : void
    {
      $this->so_item_id = $so_item_id;
    }
    
    public function getTax_rate_id(): string
    {
     return (string)$this->tax_rate_id;
    }
    
    public function setTax_rate_id(int $tax_rate_id) : void
    {
      $this->tax_rate_id = $tax_rate_id;
    }
        
     public function getDate_added(): DateTimeImmutable
    {
      /** @var DateTimeImmutable $this->date_added */
      return $this->date_added;
    }
    
    public function setDate_added(DateTime $date_added) : void
    {
      $this->date_added = $date_added;
    }
    
    public function getProduct_id(): string
    {
     return (string)$this->product_id;
    }
    
    public function setProduct_id(int $product_id) : void
    {
      $this->product_id = $product_id;
    }
    
    public function getTask_id(): string
    {
      return (string)$this->task_id;
    }
    
    public function setTask_id(int $task_id) : void
    {
      $this->task_id = $task_id;
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
      $this->description = $description;
    }
    
    public function getQuantity(): float|null
    {
     return $this->quantity;
    }
    
    public function setQuantity(float $quantity) : void
    {
      $this->quantity = $quantity;
    }
    
    public function getPrice(): ?float
    {
     return $this->price;
    }
    
    public function setPrice(float $price) : void
    {
      $this->price = $price;
    }
    
    public function getDiscount_amount(): ?float
    {
     return $this->discount_amount;
    }
    
    public function setDiscount_amount(float $discount_amount) : void
    {
      $this->discount_amount = $discount_amount;
    }
    
    // For Charges and Allowances see the extension table InvItemAllowanceCharges
    // which extends this entity by means of inv_item_id
    
    public function getOrder(): int|null
    {
     return $this->order;
    }
    
    public function setOrder(int $order) : void
    {
      $this->order = $order;
    }
    
    public function getIs_recurring(): ?bool
    {
      return $this->is_recurring;
    }
    
    public function setIs_recurring(bool $is_recurring) : void
    {
      $this->is_recurring = $is_recurring;
    }
    
    public function getDate() : DateTimeImmutable  
    {
        return $this->date;
    }    
    
    public function setDate(DateTimeImmutable $date): void
    {
      $this->date = $date;
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
    
    public function setBelongs_to_vat_invoice(int $belongs_to_vat_invoice) : void
    {
      $this->belongs_to_vat_invoice = $belongs_to_vat_invoice;        
    }    
    
    public function getBelongs_to_vat_invoice() : string
    {
      return (string)$this->belongs_to_vat_invoice;  
    }

    public function getDelivery_id(): string
    {
      return (string)$this->delivery_id;
    }
    
    public function setDelivery_id(int $delivery_id) : void
    {
      $this->delivery_id =  $delivery_id;
    }
    
    public function getNote(): ?string
    {
      return $this->note;
    }
    
    public function setNote(string $note): void
    {
      $this->note = $note;
    }  
    
    public function isNewRecord(): bool
    {
      return $this->getId() === null;
    }
}