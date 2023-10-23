<?php

declare(strict_types=1); 

namespace App\Invoice\Entity;

use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Entity;
use Cycle\Annotated\Annotation\Relation\BelongsTo;
use App\Invoice\Entity\Client;
  
 #[Entity(repository: \App\Invoice\ClientPeppol\ClientPeppolRepository::class)]
 
 class ClientPeppol
 { 
     #[BelongsTo(target:Client::class, nullable: false, fkAction:'NO ACTION')]
     private ?Client $client = null;
     
     #[Column(type: 'primary')]
     private ?int $id =  null;
     
     #[Column(type:'integer(11)', nullable: false)]
     private ?int $client_id =  null;
     
     #[Column(type:'string(100)', nullable: false)]
     private string $endpointid =  '';
     
     #[Column(type:'string(4)', nullable: false)]
     private string $endpointid_schemeid =  '';
     
     #[Column(type:'string(100)', nullable: false)]
     private string $identificationid =  '';
     
     #[Column(type:'string(4)', nullable: false)]
     private string $identificationid_schemeid =  '';
     
     #[Column(type:'string(100)', nullable: false)]
     private string $taxschemecompanyid =  '';
     
     #[Column(type:'string(7)', nullable: false)]
     private string $taxschemeid =  '';
     
     #[Column(type:'string(100)', nullable: false)]
     private string $legal_entity_registration_name =  '';
     
     #[Column(type:'string(100)', nullable: false)]
     private string $legal_entity_companyid =  '';
     
     #[Column(type:'string(5)', nullable: false)]
     private string $legal_entity_companyid_schemeid =  '';
     
     #[Column(type:'string(50)', nullable: false)]
     private string $legal_entity_company_legal_form =  '';
     
     // Bank Identifier code 
     #[Column(type:'string(20)', nullable: false)]
     private string $financial_institution_branchid=  '';
     
     // Client's Bookkeeping account code
     // @see https://docs.peppol.eu/poacc/billing/3.0/syntax/ubl-invoice/cbc-AccountingCost/
     #[Column(type:'string(30)', nullable: false)]
     private string $accounting_cost ='';
     
     #[Column(type:'string(20)', nullable: false)]
     private string $supplier_assigned_accountid = '';
     
     #[Column(type:'string(20)', nullable: false)]
     private string $buyer_reference = '';
     
     public function __construct(
         int $id = null,
         int $client_id = null,
         string $endpointid = '',
         string $endpointid_schemeid = '',
         string $identificationid = '',
         string $identificationid_schemeid = '',
         string $taxschemecompanyid = '',
         string $taxschemeid = '',
         string $legal_entity_registration_name = '',
         string $legal_entity_companyid = '',
         string $legal_entity_companyid_schemeid = '',
         string $legal_entity_company_legal_form = '',
         string $financial_institution_branchid = '',
         string $accounting_cost = '',
         string $supplier_assigned_accountid = '',
         string $buyer_reference = '',
     )
     {
         $this->id=$id;
         $this->client_id=$client_id;
         $this->endpointid=$endpointid;
         $this->endpointid_schemeid=$endpointid_schemeid;
         $this->identificationid=$identificationid;
         $this->identificationid_schemeid=$identificationid_schemeid;
         $this->taxschemecompanyid=$taxschemecompanyid;
         $this->taxschemeid=$taxschemeid;
         $this->legal_entity_registration_name=$legal_entity_registration_name;
         $this->legal_entity_companyid=$legal_entity_companyid;
         $this->legal_entity_companyid_schemeid=$legal_entity_companyid_schemeid;
         $this->legal_entity_company_legal_form=$legal_entity_company_legal_form;
         $this->financial_institution_branchid=$financial_institution_branchid;
         $this->accounting_cost=$accounting_cost;
         $this->supplier_assigned_accountid=$supplier_assigned_accountid;
         $this->buyer_reference=$buyer_reference;
     }
    
    public function getClient() : ?Client
    {
      return $this->client;
    }
    
    public function setClient(?Client $client): void
    {
      $this->client = $client;
    }
    
    public function getId(): string
    {
     return (string)$this->id;
    }
    
    public function setId(int $id) : void
    {
      $this->id =  $id;
    }
    
    public function getClient_id(): string
    {
     return (string)$this->client_id;
    }
    
    public function setClient_id(int $client_id) : void
    {
      $this->client_id =  $client_id;
    }
    
    public function getEndpointid(): string
    {
     return $this->endpointid;
    }
    
    public function setEndpointid(string $endpointid) : void
    {
      $this->endpointid =  $endpointid;
    }
    
    public function getEndpointid_schemeid(): string
    {
     return $this->endpointid_schemeid;
    }
    
    public function setEndpointid_schemeid(string $endpointid_schemeid) : void
    {
      $this->endpointid_schemeid =  $endpointid_schemeid;
    }
    
    public function getIdentificationid(): string
    {
     return $this->identificationid;
    }
    
    public function setIdentificationid(string $identificationid) : void
    {
      $this->identificationid =  $identificationid;
    }
    
    public function getIdentificationid_schemeid(): string
    {
     return $this->identificationid_schemeid;
    }
    
    public function setIdentificationid_schemeid(string $identificationid_schemeid) : void
    {
      $this->identificationid_schemeid =  $identificationid_schemeid;
    }
    
    public function getTaxschemecompanyid(): string
    {
     return $this->taxschemecompanyid;
    }
    
    public function setTaxschemecompanyid(string $taxschemecompanyid) : void
    {
      $this->taxschemecompanyid =  $taxschemecompanyid;
    }
    
    public function getTaxschemeid(): string
    {
     return $this->taxschemeid;
    }
    
    public function setTaxschemeid(string $taxschemeid) : void
    {
      $this->taxschemeid =  $taxschemeid;
    }
    
    public function getLegal_entity_registration_name(): string
    {
       return $this->legal_entity_registration_name;
    }
    
    public function setLegal_entity_registration_name(string $legal_entity_registration_name) : void
    {
      $this->legal_entity_registration_name =  $legal_entity_registration_name;
    }
    
    public function getLegal_entity_companyid(): string
    {
     return $this->legal_entity_companyid;
    }
    
    public function setLegal_entity_companyid(string $legal_entity_companyid) : void
    {
      $this->legal_entity_companyid =  $legal_entity_companyid;
    }
    
    public function getLegal_entity_companyid_schemeid(): string
    {
     return $this->legal_entity_companyid_schemeid;
    }
    
    public function setLegal_entity_companyid_schemeid(string $legal_entity_companyid_schemeid) : void
    {
      $this->legal_entity_companyid_schemeid =  $legal_entity_companyid_schemeid;
    }
    
    public function getLegal_entity_company_legal_form(): string
    {
       return $this->legal_entity_company_legal_form;
    }
    
    public function setLegal_entity_company_legal_form(string $legal_entity_company_legal_form) : void
    {
      $this->legal_entity_company_legal_form =  $legal_entity_company_legal_form;
    }
    
    public function getFinancial_institution_branchid(): string
    {
       return $this->financial_institution_branchid;
    }
    
    public function setFinancial_institution_branchid(string $financial_institution_branchid) : void
    {
      $this->financial_institution_branchid =  $financial_institution_branchid;
    }
    
    public function getAccountingCost(): string
    {
        return $this->accounting_cost;
    }
    
    public function setAccountingCost(string $accounting_cost) : void 
    {
        $this->accounting_cost = $accounting_cost;
    }
    
    public function getSupplierAssignedAccountId(): string
    {
        return $this->supplier_assigned_accountid;
    }
    
    public function setSupplierAssignedAccountId(string $supplier_assigned_accountid) : void 
    {
        $this->supplier_assigned_accountid = $supplier_assigned_accountid;
    }
    
    public function getBuyerReference(): string
    {
        return $this->buyer_reference;
    }
    
    public function setBuyerReference(string $buyer_reference) : void 
    {
        $this->buyer_reference = $buyer_reference;
    }
    
    public function nullifyRelationOnChange(int $client_id) : void 
    {
        if ($this->client_id <> $client_id) {
            $this->client = null;
        }
    }
 }