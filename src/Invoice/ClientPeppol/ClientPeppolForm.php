<?php

declare(strict_types=1);

namespace App\Invoice\ClientPeppol;

use Yiisoft\Form\FormModel;
use Yiisoft\Validator\Rule\Required;

final class ClientPeppolForm extends FormModel
{    
    private ?int $id=null;
    private ?int $client_id=null;       
    private ?string $accounting_cost='';
    private ?string $buyer_reference='';
    private ?string $endpointid='';
    private ?string $endpointid_schemeid='';
    private ?string $financial_institution_branchid='';
    private ?string $identificationid='';
    private ?string $identificationid_schemeid='';
    private ?string $legal_entity_registration_name='';
    private ?string $legal_entity_companyid='';
    private ?string $legal_entity_companyid_schemeid='';
    private ?string $legal_entity_company_legal_form='';
    private ?string $taxschemecompanyid='';
    private ?string $taxschemeid='';
    private ?string $supplier_assigned_accountid='';
    
    public function getId() : int|null
    {
      return $this->id;
    }

    public function getClient_id() : int|null
    {
      return $this->client_id;
    }

    public function getAccounting_cost() : string|null
    {
      return $this->accounting_cost;
    }
    
    public function getBuyer_reference() : string|null
    {
      return $this->buyer_reference;
    }
    
    public function getEndpointid() : string|null
    {
      return $this->endpointid;
    }

    public function getEndpointid_schemeid() : string|null
    {
      return $this->endpointid_schemeid;
    }
    
    public function getFinancial_institution_branchid() : string|null 
    {
      return $this->financial_institution_branchid;
    }    
    
    
    public function getIdentificationid() : string|null
    {
      return $this->identificationid;
    }

    public function getIdentificationid_schemeid() : string|null
    {
      return $this->identificationid_schemeid;
    }
    
    public function getLegal_entity_registration_name() : string|null
    {
      return $this->legal_entity_registration_name;
    }

    public function getLegal_entity_companyid() : string|null
    {
      return $this->legal_entity_companyid;
    }

    public function getLegal_entity_companyid_schemeid() : string|null
    {
      return $this->legal_entity_companyid_schemeid;
    }

    public function getLegal_entity_company_legal_form() : string|null
    {
      return $this->legal_entity_company_legal_form;
    }
    
    public function getTaxschemecompanyid() : string|null
    {
      return $this->taxschemecompanyid;
    }

    public function getTaxschemeid() : string|null
    {
      return $this->taxschemeid;
    }
    
    public function getSupplierAssignedAccountId() : string|null
    {
      return $this->supplier_assigned_accountid;
    }
    
    /**
     * @return string
     * @psalm-return ''
     */
    public function getFormName(): string
    {
      return '';
    }

    public function getRules(): array    {
      return [
        'accounting_cost' => [new Required()],
        'buyer_reference' => [new Required()],
        'endpointid' => [new Required()],
        'endpointid_schemeid' => [new Required()],
        'financial_institution_branchid' => [new Required()],
        'identificationid' => [new Required()],
        'identificationid_schemeid' => [new Required()],
        'legal_entity_companyid' => [new Required()],
        'legal_entity_companyid_schemeid' => [new Required()],
        'legal_entity_registration_name' => [new Required()],
        'legal_entity_company_legal_form' => [new Required()],
        'supplier_assigned_accountid' => [new Required()],
        'taxschemecompanyid' => [new Required()],
        'taxschemeid' => [new Required()],  
      ];
    }
}
