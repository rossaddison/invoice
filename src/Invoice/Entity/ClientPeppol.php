<?php

declare(strict_types=1);

namespace App\Invoice\Entity;

use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Entity;
use Cycle\Annotated\Annotation\Relation\BelongsTo;

#[Entity(repository: \App\Invoice\ClientPeppol\ClientPeppolRepository::class)]

class ClientPeppol
{
    #[BelongsTo(target: Client::class, nullable: false, fkAction: 'NO ACTION')]
    private ?Client $client = null;

    public function __construct(
        #[Column(type: 'primary')]
        private ?int $id = null,
        #[Column(type: 'integer(11)', nullable: false)]
        private ?int $client_id = null,
        #[Column(type: 'string(100)', nullable: false)]
        private string $endpointid = '',
        #[Column(type: 'string(4)', nullable: false)]
        private string $endpointid_schemeid = '',
        #[Column(type: 'string(100)', nullable: false)]
        private string $identificationid = '',
        #[Column(type: 'string(4)', nullable: false)]
        private string $identificationid_schemeid = '',
        #[Column(type: 'string(100)', nullable: false)]
        private string $taxschemecompanyid = '',
        #[Column(type: 'string(7)', nullable: false)]
        private string $taxschemeid = '',
        #[Column(type: 'string(100)', nullable: false)]
        private string $legal_entity_registration_name = '',
        #[Column(type: 'string(100)', nullable: false)]
        private string $legal_entity_companyid = '',
        #[Column(type: 'string(5)', nullable: false)]
        private string $legal_entity_companyid_schemeid = '',
        #[Column(type: 'string(50)', nullable: false)]
        private string $legal_entity_company_legal_form = '',
        #[Column(type: 'string(20)', nullable: false)]
        private string $financial_institution_branchid = '',
        #[Column(type: 'string(30)', nullable: false)]
        private string $accounting_cost = '',
        #[Column(type: 'string(20)', nullable: false)]
        private string $supplier_assigned_accountid = '',
        #[Column(type: 'string(20)', nullable: false)]
        private string $buyer_reference = '')
    {
    }

    public function getClient(): ?Client
    {
        return $this->client;
    }

    public function setClient(?Client $client): void
    {
        $this->client = $client;
    }

    public function getId(): string
    {
        return (string) $this->id;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function getClient_id(): string
    {
        return (string) $this->client_id;
    }

    public function setClient_id(int $client_id): void
    {
        $this->client_id = $client_id;
    }

    public function getEndpointid(): string
    {
        return $this->endpointid;
    }

    public function setEndpointid(string $input): void
    {
        $this->endpointid = $input;
    }

    public function getEndpointid_schemeid(): string
    {
        return $this->endpointid_schemeid;
    }

    public function setEndpointid_schemeid(string $input): void
    {
        $this->endpointid_schemeid = $input;
    }

    public function getIdentificationid(): string
    {
        return $this->identificationid;
    }

    public function setIdentificationid(string $input): void
    {
        $this->identificationid = $input;
    }

    public function getIdentificationid_schemeid(): string
    {
        return $this->identificationid_schemeid;
    }

    public function setIdentificationid_schemeid(string $input): void
    {
        $this->identificationid_schemeid = $input;
    }

    public function getTaxschemecompanyid(): string
    {
        return $this->taxschemecompanyid;
    }

    public function setTaxschemecompanyid(string $input): void
    {
        $this->taxschemecompanyid = $input;
    }

    public function getTaxschemeid(): string
    {
        return $this->taxschemeid;
    }

    public function setTaxschemeid(string $input): void
    {
        $this->taxschemeid = $input;
    }

    public function getLegal_entity_registration_name(): string
    {
        return $this->legal_entity_registration_name;
    }

    public function setLegal_entity_registration_name(string $input): void
    {
        $this->legal_entity_registration_name = $input;
    }

    public function getLegal_entity_companyid(): string
    {
        return $this->legal_entity_companyid;
    }

    public function setLegal_entity_companyid(string $input): void
    {
        $this->legal_entity_companyid = $input;
    }

    public function getLegal_entity_companyid_schemeid(): string
    {
        return $this->legal_entity_companyid_schemeid;
    }

    public function setLegal_entity_companyid_schemeid(string $input): void
    {
        $this->legal_entity_companyid_schemeid = $input;
    }

    public function getLegal_entity_company_legal_form(): string
    {
        return $this->legal_entity_company_legal_form;
    }

    public function setLegal_entity_company_legal_form(string $input): void
    {
        $this->legal_entity_company_legal_form = $input;
    }

    public function getFinancial_institution_branchid(): string
    {
        return $this->financial_institution_branchid;
    }

    public function setFinancial_institution_branchid(string $input): void
    {
        $this->financial_institution_branchid = $input;
    }

    public function getAccountingCost(): string
    {
        return $this->accounting_cost;
    }

    public function setAccountingCost(string $input): void
    {
        $this->accounting_cost = $input;
    }

    public function getSupplierAssignedAccountId(): string
    {
        return $this->supplier_assigned_accountid;
    }

    public function setSupplierAssignedAccountId(string $input): void
    {
        $this->supplier_assigned_accountid = $input;
    }

    public function getBuyerReference(): string
    {
        return $this->buyer_reference;
    }

    public function setBuyerReference(string $input): void
    {
        $this->buyer_reference = $input;
    }
}
