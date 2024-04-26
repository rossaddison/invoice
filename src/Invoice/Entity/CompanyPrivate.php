<?php
declare(strict_types=1); 

namespace App\Invoice\Entity;

use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Entity;
use Cycle\ORM\Entity\Behavior;
use Cycle\Annotated\Annotation\Relation\BelongsTo;
use \DateTime;
use \DateTimeImmutable; 
use App\Invoice\Entity\Company;

 #[Entity(repository: \App\Invoice\CompanyPrivate\CompanyPrivateRepository::class)]
 #[Behavior\UpdatedAt(field: 'date_modified', column: 'date_modified')]       
 class CompanyPrivate
 {       
    #[BelongsTo(target:Company::class, nullable: false, fkAction: "NO ACTION")]
    private ?Company $company = null;     
    #[Column(type: 'integer(11)', nullable: false)]
    private ?int $company_id =  null;    
    
    #[Column(type: 'primary')]
    private ?int $id =  null;
     
    #[Column(type: 'text', nullable: true)]
    private ?string $vat_id =  '';
     
    #[Column(type: 'text', nullable: true)]
    private ?string $tax_code =  '';
     
    #[Column(type: 'string(34)', nullable: true)]
    private ?string $iban =  '';
     
    #[Column(type: 'string(14)', nullable: true)]
    private ?string $gln =  null;
     
    #[Column(type: 'string(7)', nullable: true)]
    private ?string $rcc =  '';
    
    #[Column(type: 'string(150)', nullable: true)]
    private ?string $logo_filename =  '';
    
    #[Column(type: 'int', nullable: false, default: 80)]
    private ?int $logo_width = null;
     
    #[Column(type: 'int', nullable: false, default: 40)]
    private ?int $logo_height = null;
    
    #[Column(type: 'int', nullable: false, default: 10)]
    private ?int $logo_margin = null;
    
    #[Column(type: 'datetime')]
    private DateTimeImmutable $date_created;
     
    #[Column(type: 'datetime')]
    private DateTimeImmutable $date_modified;
    
    #[Column(type:'date', nullable: true)]
    private mixed $start_date;
    
    #[Column(type:'date', nullable: true)]
    private mixed $end_date;
    
    public function __construct(
         int $id = null,
         int $company_id = null,
         string $vat_id = '',
         string $tax_code = '',
         string $iban = '',
         string $gln = '',
         string $rcc = '',
         string $logo_filename = '',
         int $logo_width = null,
         int $logo_height = null,
         int $logo_margin = null,   
         mixed $start_date = null,
         mixed $end_date = null,
     )
     {
         $this->id=$id;
         $this->company_id=$company_id;
         $this->vat_id=$vat_id;
         $this->tax_code=$tax_code;
         $this->iban=$iban;
         $this->gln=$gln;
         $this->rcc=$rcc;
         $this->logo_filename=$logo_filename;
         $this->logo_width=$logo_width;
         $this->logo_height=$logo_height;
         $this->logo_margin=$logo_margin;
         $this->date_created= new \DateTimeImmutable();
         $this->date_modified= new \DateTimeImmutable();
         $this->start_date = $start_date;
         $this->end_date = $end_date;
    }
    
    //get relation $company
    public function getCompany() : ?Company
    {
      return $this->company;
    }
    
    //set relation $company
    public function setCompany(?Company $company): void
    {
        $this->company = $company;
    }
    
    public function getId(): int|null
    {
     return $this->id;
    }
    
    public function setId(int $id) : void
    {
      $this->id =  $id;
    }
    
    public function getCompany_id(): string
    {
     return (string)$this->company_id;
    }
    
    public function setCompany_id(int $company_id) : void
    {
      $this->company_id =  $company_id;
    }
    
    public function getVat_id(): string
    {
     return (string)$this->vat_id;
    }
    
    public function setVat_id(string $vat_id) : void
    {
      $this->vat_id =  $vat_id;
    }
    
    public function getTax_code(): string|null
    {
       return $this->tax_code;
    }
    
    public function setTax_code(string $tax_code) : void
    {
      $this->tax_code =  $tax_code;
    }
    
    public function getIban(): string|null
    {
       return $this->iban;
    }
    
    public function setIban(string $iban) : void
    {
      $this->iban =  $iban;
    }
    
    public function getGln(): string|null
    {
       return $this->gln;
    }
    
    public function setGln(string $gln) : void
    {
      $this->gln =  $gln;
    }
    
    public function getRcc(): string|null
    {
       return $this->rcc;
    }
    
    public function setRcc(string $rcc) : void
    {
      $this->rcc =  $rcc;
    }
    
    public function getLogo_filename(): string|null
    {
       return $this->logo_filename;
    }
    
    public function setLogo_filename(string $logo_filename) : void
    {
      $this->logo_filename = $logo_filename;
    }
    
    public function getLogo_width(): int|null
    {
       return $this->logo_width;
    }
    
    public function setLogo_width(int $logo_width) : void
    {
      $this->logo_width = $logo_width;
    }
    
    public function getLogo_height(): int|null
    {
       return $this->logo_height;
    }
    
    public function setLogo_height(int $logo_height) : void
    {
      $this->logo_height = $logo_height;
    }
    
    public function getLogo_Margin(): int|null
    {
       return $this->logo_margin;
    }
    
    public function setLogo_margin(int $logo_margin) : void
    {
      $this->logo_margin = $logo_margin;
    }
    
    public function getDate_created(): DateTimeImmutable
    {
       return $this->date_created;
    }
        
    public function getDate_modified(): DateTimeImmutable
    {
       return $this->date_modified;
    }
    
     //cycle 
    public function getStart_date() : DateTimeImmutable|null  
    {
        /** @var DateTimeImmutable|null $this->start_date */
        return $this->start_date;
    }    
    
    public function setStart_date(?DateTime $start_date): void
    {
        $this->start_date = $start_date;
    }
    
     //cycle 
    public function getEnd_date() : DateTimeImmutable|null  
    {
        /** @var DateTimeImmutable|null $this->end_date */
        return $this->end_date;
    }    
    
    public function setEnd_date(?DateTime $end_date): void
    {
        $this->end_date = $end_date;
    }
    
    public function nullifyRelationOnChange(int $company_id) : void {
        if ($this->company_id <> $company_id) {
           $this->company = null;
        }
    }
    
    public function isNewRecord(): bool
    {
       return $this->getId() === null;
    }
}