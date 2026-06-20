<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\ClientPeppol\Trait;

use App\Infrastructure\Persistence\Client\Client;
use App\Invoice\ClientPeppol\ClientPeppolRepository;
use RequireId;

/**
 * @method int requireId(?int $id, string $context)
 */
trait ClientPeppolTrait2
{

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

    public function getLegalEntityRegistrationName(): string
    {
        return $this->legal_entity_registration_name;
    }

    public function setLegalEntityRegistrationName(string $input): void
    {
        $this->legal_entity_registration_name = $input;
    }

    public function getLegalEntityCompanyid(): string
    {
        return $this->legal_entity_companyid;
    }

    public function setLegalEntityCompanyid(string $input): void
    {
        $this->legal_entity_companyid = $input;
    }

    public function getLegalEntityCompanyidSchemeid(): string
    {
        return $this->legal_entity_companyid_schemeid;
    }

    public function setLegalEntityCompanyidSchemeid(string $input): void
    {
        $this->legal_entity_companyid_schemeid = $input;
    }

    public function getLegalEntityCompanyLegalForm(): string
    {
        return $this->legal_entity_company_legal_form;
    }

    public function setLegalEntityCompanyLegalForm(string $input): void
    {
        $this->legal_entity_company_legal_form = $input;
    }

    public function getFinancialInstitutionBranchid(): string
    {
        return $this->financial_institution_branchid;
    }

    public function setFinancialInstitutionBranchid(string $input): void
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
}
