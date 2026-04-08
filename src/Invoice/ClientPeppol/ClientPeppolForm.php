<?php

declare(strict_types=1);

namespace App\Invoice\ClientPeppol;

use App\Invoice\Entity\ClientPeppol;
use Yiisoft\FormModel\FormModel;
use Yiisoft\Validator\Rule\Length;
use Yiisoft\Validator\Rule\Email;
use Yiisoft\Validator\Rule\Required;

final class ClientPeppolForm extends FormModel
{
    private ?int $id = null;
    private ?int $client_id = null;

    #[Required, Length(min: 0, max: 30)]
    private ?string $accounting_cost = '';

    #[Required, Length(min: 0, max: 20)]
    private ?string $buyer_reference = '';

    #[Required, Email, Length(min: 0, max: 100)]
    private ?string $endpointid = '';

    #[Required, Length(min: 0, max: 4)]
    private ?string $endpointid_schemeid = '';

    #[Required, Length(min: 0, max: 20)]
    private ?string $financial_institution_branchid = '';

    #[Required, Length(min: 0, max: 100)]
    private ?string $identificationid = '';

    #[Required, Length(min: 0, max: 4)]
    private ?string $identificationid_schemeid = '';

    #[Required, Length(min: 0, max: 100)]
    private ?string $legal_entity_registration_name = '';

    #[Required, Length(min: 0, max: 100)]
    private ?string $legal_entity_companyid = '';

    #[Required, Length(min: 0, max: 5)]
    private ?string $legal_entity_companyid_schemeid = '';

    #[Required, Length(min: 0, max: 50)]
    private ?string $legal_entity_company_legal_form = '';

    #[Required, Length(min: 0, max: 100)]
    private ?string $taxschemecompanyid = '';

    #[Required, Length(min: 0, max: 7)]
    private ?string $taxschemeid = '';

    #[Required, Length(min: 0, max: 20)]
    private ?string $supplier_assigned_accountid = '';

    public function __construct(ClientPeppol $client_peppol)
    {
        $this->id = (int) $client_peppol->getId();
        $this->client_id = (int) $client_peppol->getClientId();
        $this->accounting_cost = $client_peppol->getAccountingCost();
        $this->buyer_reference = $client_peppol->getBuyerReference();
        $this->endpointid = $client_peppol->getEndpointid();
        $this->endpointid_schemeid = $client_peppol->getEndpointidSchemeid();
        $this->financial_institution_branchid =
                $client_peppol->getFinancialInstitutionBranchid();
        $this->identificationid = $client_peppol->getIdentificationid();
        $this->identificationid_schemeid =
                $client_peppol->getIdentificationidSchemeid();
        $this->legal_entity_registration_name =
                $client_peppol->getLegalEntityRegistrationName();
        $this->legal_entity_companyid =
                $client_peppol->getLegalEntityCompanyid();
        $this->legal_entity_companyid_schemeid =
                $client_peppol->getLegalEntityCompanyidSchemeid();
        $this->legal_entity_company_legal_form =
                $client_peppol->getLegalEntityCompanyLegalForm();
        $this->taxschemecompanyid = $client_peppol->getTaxschemecompanyid();
        $this->taxschemeid = $client_peppol->getTaxschemeid();
        $this->supplier_assigned_accountid =
                $client_peppol->getSupplierAssignedAccountId();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getClientId(): ?int
    {
        return $this->client_id;
    }

    public function getAccountingCost(): ?string
    {
        return $this->accounting_cost;
    }

    public function getBuyerReference(): ?string
    {
        return $this->buyer_reference;
    }

    public function getEndpointid(): ?string
    {
        return $this->endpointid;
    }

    public function getEndpointidSchemeid(): ?string
    {
        return $this->endpointid_schemeid;
    }

    public function getFinancialInstitutionBranchid(): ?string
    {
        return $this->financial_institution_branchid;
    }

    public function getIdentificationid(): ?string
    {
        return $this->identificationid;
    }

    public function getIdentificationidSchemeid(): ?string
    {
        return $this->identificationid_schemeid;
    }

    public function getLegalEntityRegistrationName(): ?string
    {
        return $this->legal_entity_registration_name;
    }

    public function getLegalEntityCompanyid(): ?string
    {
        return $this->legal_entity_companyid;
    }

    public function getLegalEntityCompanyidSchemeid(): ?string
    {
        return $this->legal_entity_companyid_schemeid;
    }

    public function getLegalEntityCompanyLegalForm(): ?string
    {
        return $this->legal_entity_company_legal_form;
    }

    public function getTaxschemecompanyid(): ?string
    {
        return $this->taxschemecompanyid;
    }

    public function getTaxschemeid(): ?string
    {
        return $this->taxschemeid;
    }

    public function getSupplierAssignedAccountId(): ?string
    {
        return $this->supplier_assigned_accountid;
    }

    /**
     * @return string
     * @psalm-return ''
     */
    #[\Override]
    public function getFormName(): string
    {
        return '';
    }
}
