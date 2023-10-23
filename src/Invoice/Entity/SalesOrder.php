<?php

declare(strict_types=1); 

namespace App\Invoice\Entity;

use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Entity;
use Cycle\Annotated\Annotation\Relation\BelongsTo;
use Cycle\ORM\Entity\Behavior;
use \DateTimeImmutable;
use App\Invoice\Entity\Client;
use App\Invoice\Entity\Group;
use App\User\User;
  
#[Entity(repository: \App\Invoice\SalesOrder\SalesOrderRepository::class)]
#[Behavior\CreatedAt(field: 'date_created', column: 'date_created')] 
#[Behavior\UpdatedAt(field: 'date_modified', column: 'date_modified')]

class SalesOrder
{    
    #[BelongsTo(target:Client::class, nullable: false, fkAction:'NO ACTION')]
    private ?Client $client = null; 
    
    #[BelongsTo(target:Group::class, nullable: false, fkAction:'NO ACTION')]
    private ?Group $group = null;
        
    #[BelongsTo(target:User::class, nullable: false)]
    private ?User $user = null;
        
    #[Column(type: 'primary')]
    private ?int $id =  null;
    
    #[Column(type: 'integer(11)', nullable:false, default:0)]
    private ?int $quote_id =  null;
        
    #[Column(type: 'integer(11)', nullable:false, default:0)]
    private ?int $inv_id =  null;
     
    #[Column(type: 'integer(11)', nullable:false)]
    private ?int $user_id =  null;
     
    #[Column(type: 'integer(11)', nullable:false)]
    private ?int $client_id =  null;
        
    #[Column(type: 'integer(11)', nullable:false)]
    private ?int $group_id =  null;
    
    #[Column(type: 'tinyInteger(2)', nullable:false, default:1)]
    private ?int $status_id =  null;
    
    #[Column(type: 'datetime', nullable:false)]
    private DateTimeImmutable $date_created;
     
    #[Column(type: 'datetime', nullable:false)]
    private DateTimeImmutable $date_modified;
     
    #[Column(type: 'datetime', nullable:false)]
    private DateTimeImmutable $date_expires;
     
    #[Column(type: 'string(100)', nullable:true)]
    private ?string $number =  '';
    
    #[Column(type: 'string(100)', nullable:true)]
    private ?string $client_po_number =  '';
    
    #[Column(type: 'string(100)', nullable:true)]
    private ?string $client_po_line_number =  '';
    
    #[Column(type: 'string(100)', nullable:true)]
    private ?string $client_po_person =  '';
     
    #[Column(type: 'decimal(20,2)', nullable: false, default: 0.00)]
    private ?float $discount_amount =  0.00;
     
    #[Column(type: 'decimal(20,2)', nullable: false, default: 0.00)]
    private ?float $discount_percent =  0.00;
     
    #[Column(type: 'string(32)', nullable:true)]
    private string $url_key =  '';
     
    #[Column(type: 'string(90)', nullable:true)]
    private ?string $password =  '';
        
    #[Column(type: 'longText', nullable:true)]
    private ?string $payment_term =  '';
    
    #[Column(type: 'longText', nullable:true)]
    private ?string $notes =  '';
         
    public function __construct(
        // The purchase order is derived from the quote =>quote_id    
        // If a contract has been established between the supplier and the client, use the contract reference    
        int $quote_id = null,    
        int $inv_id = null,
        int $client_id = null,
        int $user_id = null,
        int $group_id = null,
        int $status_id = null,
        string $number = '',
        string $client_po_number = '',
        string $client_po_line_number = '',
        string $client_po_person = '',
        float $discount_amount = 0.00,
        float $discount_percent = 0.00,
        string $url_key = '',
        string $password = '',
        string $notes = '',
        string $payment_term = ''
    )
    {         
        $this->quote_id=$quote_id;
        $this->inv_id=$inv_id;
        $this->client_id=$client_id;
        $this->group_id=$group_id;
        $this->user_id=$user_id;
        $this->status_id=$status_id;
        $this->number=$number;
        $this->client_po_number=$client_po_number;
        $this->client_po_line_number=$client_po_line_number;
        $this->client_po_person=$client_po_person;
        $this->discount_amount=$discount_amount;
        $this->discount_percent=$discount_percent;
        $this->url_key=$url_key;
        $this->password=$password;
        $this->notes=$notes;
        $this->payment_term=$payment_term;
        $this->date_modified = new \DateTimeImmutable();
        $this->date_created = new \DateTimeImmutable();
        $this->date_expires = new \DateTimeImmutable();
    }
    
    public function getClient() : ?Client
    {
      return $this->client;
    }
    
    public function setClient(?Client $client) : void {
        $this->client = $client;
    }
    
    public function getGroup() : ?Group
    {
      return $this->group;
    }
    
    public function setGroup(?Group $group) : void {
        $this->group = $group;
    }
    
    public function getUser() : ?User
    {
      return $this->user;
    }
    
    public function setUser(User $user): void
    {
        $this->user = $user;
    }
    
    public function getPaymentTerm() : ?string
    {
      return $this->payment_term;
    }
    
    public function setPaymentTerm(string $payment_term) : void {
        $this->payment_term = $payment_term;
    }
    
    /**
     * @return null|numeric-string
     */
    public function getId(): string|null
    {
        return $this->id === null ? null : (string)$this->id;
    }
    
    public function setId(int $id) : void
    {
      $this->id =  $id;
    }
    
    public function getUser_id(): string
    {
     return (string)$this->user_id;
    }
    
    public function setUser_id(int $user_id) : void
    {
      $this->user_id =  $user_id;
    }
    
    /**
     * @param int|null|string $quote_id
     */
    public function setQuote_id(string|int|null $quote_id) : void
    {
      $quote_id === null ? $this->quote_id = null : $this->quote_id = (int)$quote_id ;
    }
    
    public function getQuote_id(): string
    {
      return (string)$this->quote_id;        
    }
    
    public function getInv_id(): string|null 
    {
      return (string)$this->inv_id;
    }    
    
    /**
     * @param int|null|string $inv_id
     */
    public function setInv_id(string|int|null $inv_id) : void
    {
      $inv_id === null ? $this->inv_id = null : $this->inv_id = (int)$inv_id ;
    }
    
    public function getClient_id(): string
    {
     return (string)$this->client_id;
    }
    
    public function setClient_id(int $client_id) : void
    {
      $this->client_id =  $client_id;
    }
    
    public function getGroup_id(): string
    {
     return (string)$this->group_id;
    }
    
    public function setGroup_id(int $group_id) : void
    {
      $this->group_id =  $group_id;
    }
    
    public function getStatus_id(): int|null
    {
        return $this->status_id;
    } 
    
    public function getStatus(int $status_id): string
    {
        $status = '';
        switch ($status_id) {
            case 1:
                $status =  'draft'; 
                break;
            case 2: 
                $status =  'sent';
                break;
            case 3:
                $status =  'viewed';
                break;
            case 4:
                $status =  'approved';
                break;
            case 5:
                $status =  'rejected';
                break;
            case 6:
                $status =  'cancelled';
                break;
        }
        return $status;
    }    
    
    public function setStatus_id(int $status_id) : void
    {
      !in_array($status_id,[1,2,3,4,5,6,7,8,9]) ? $this->status_id = 1 : $this->status_id = $status_id ;
    }
    
    public function getDate_created(): DateTimeImmutable
    {
       return $this->date_created;  
    }
    
    public function setDate_created(DateTimeImmutable $date_created) : void
    {
       $this->date_created = $date_created;
    }
    
    public function getDate_modified(): DateTimeImmutable
    {
       return $this->date_modified;
    }
    
    public function setDate_expires() : void
    {
        $days = 1;
        $this->date_expires = (new \DateTimeImmutable('now'))->add(new \DateInterval('P'.$days.'D'));
    }
    
    public function getDate_expires(): DateTimeImmutable
    {
       return $this->date_expires;  
    }
    
    public function getNumber(): ?string
    {
       return $this->number;
    }
    
    public function setNumber(string $number) : void
    {
       $this->number =  $number;
    }
    
    public function getClient_po_number(): ?string
    {
       return $this->client_po_number;
    }
    
    public function setClient_po_number(string $client_po_number) : void
    {
       $this->client_po_number =  $client_po_number;
    }
    
    public function getClient_po_line_number(): ?string
    {
       return $this->client_po_line_number;
    }
    
    public function setClient_po_line_number(string $client_po_line_number) : void
    {
       $this->client_po_line_number =  $client_po_line_number;
    }
    
    public function getClient_po_person(): ?string
    {
       return $this->client_po_person;
    }
    
    public function setClient_po_person(string $client_po_person) : void
    {
       $this->client_po_person =  $client_po_person;
    }
    
    public function getDiscount_amount(): ?float
    {
       return $this->discount_amount;
    }
    
    public function setDiscount_amount(float $discount_amount) : void
    {
       $this->discount_amount = $discount_amount;
    }
    
    public function getDiscount_percent(): ?float
    {
       return $this->discount_percent;
    }
    
    public function setDiscount_percent(float $discount_percent) : void
    {
      $this->discount_percent =  $discount_percent;
    }
    
    public function getUrl_key(): string
    {
       return $this->url_key;
    }
    
    public function setUrl_key(string $url_key) : void
    {
      $this->url_key =  $url_key;
    }
    
    public function getPassword(): ?string
    {
       return $this->password;
    }
    
    public function setPassword(string $password) : void
    {
      $this->password =  $password;
    }
    
    public function getNotes(): ?string
    {
       return $this->notes;
    }
    
    public function setNotes(string $notes) : void
    {
      $this->notes =  $notes;
    }
    
    public function isNewRecord(): bool
    {
        return null===$this->getId() ? true : false;
    }
}