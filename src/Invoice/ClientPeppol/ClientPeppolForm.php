<?php

declare(strict_types=1);

namespace App\Invoice\ClientPeppol;

use App\Invoice\Entity\ClientPeppol;
use Yiisoft\FormModel\FormModel;
use Yiisoft\Validator\Rule\Required;

final class ClientPeppolForm extends FormModel
{
    private ?int $id = null;
    private ?int $client_id = null;
    #[Required]
    private ?string $accounting_cost = '';
    #[Required]
    private ?string $buyer_reference = '';
    #[Required]
    private ?string $endpointid = '';
    #[Required]
    private ?string $endpointid_schemeid = '';
    #[Required]
    private ?string $financial_institution_branchid = '';
    #[Required]
    private ?string $identificationid = '';
    #[Required]
    private ?string $identificationid_schemeid = '';
    #[Required]
    private ?string $legal_entity_registration_name = '';
    #[Required]
    private ?string $legal_entity_companyid = '';
    #[Required]
    private ?string $legal_entity_companyid_schemeid = '';
    #[Required]
    private ?string $legal_entity_company_legal_form = '';
    #[Required]
    private ?string $taxschemecompanyid = '';
    #[Required]
    private ?string $taxschemeid = '';
    #[Required]
    private ?string $supplier_assigned_accountid = '';

    public function __construct(ClientPeppol $client_peppol)
    {
        $this->id = (int)$client_peppol->getId();
        $this->client_id = (int)$client_peppol->getClient_id();
        $this->accounting_cost = $client_peppol->getAccountingCost();
        $this->buyer_reference = $client_peppol->getBuyerReference();
        $this->endpointid = $client_peppol->getEndpointid();
        $this->endpointid_schemeid = $client_peppol->getEndpointid_schemeid();
        $this->financial_institution_branchid = $client_peppol->getFinancial_institution_branchid();
        $this->identificationid = $client_peppol->getIdentificationid();
        $this->identificationid_schemeid = $client_peppol->getIdentificationid_schemeid();
        $this->legal_entity_registration_name = $client_peppol->getLegal_entity_registration_name();
        $this->legal_entity_companyid = $client_peppol->getLegal_entity_companyid();
        $this->legal_entity_companyid_schemeid = $client_peppol->getLegal_entity_companyid_schemeid();
        $this->legal_entity_company_legal_form = $client_peppol->getLegal_entity_company_legal_form();
        $this->taxschemecompanyid = $client_peppol->getTaxschemecompanyid();
        $this->taxschemeid = $client_peppol->getTaxschemeid();
        $this->supplier_assigned_accountid = $client_peppol->getSupplierAssignedAccountId();
    }

    public function getId(): int|null
    {
        return $this->id;
    }

    public function getClient_id(): int|null
    {
        return $this->client_id;
    }

    public function getAccounting_cost(): string|null
    {
        return $this->accounting_cost;
    }

    public function getBuyer_reference(): string|null
    {
        return $this->buyer_reference;
    }

    public function getEndpointid(): string|null
    {
        return $this->endpointid;
    }

    public function getEndpointid_schemeid(): string|null
    {
        return $this->endpointid_schemeid;
    }

    public function getFinancial_institution_branchid(): string|null
    {
        return $this->financial_institution_branchid;
    }

    public function getIdentificationid(): string|null
    {
        return $this->identificationid;
    }

    public function getIdentificationid_schemeid(): string|null
    {
        return $this->identificationid_schemeid;
    }

    public function getLegal_entity_registration_name(): string|null
    {
        return $this->legal_entity_registration_name;
    }

    public function getLegal_entity_companyid(): string|null
    {
        return $this->legal_entity_companyid;
    }

    public function getLegal_entity_companyid_schemeid(): string|null
    {
        return $this->legal_entity_companyid_schemeid;
    }

    public function getLegal_entity_company_legal_form(): string|null
    {
        return $this->legal_entity_company_legal_form;
    }

    public function getTaxschemecompanyid(): string|null
    {
        return $this->taxschemecompanyid;
    }

    public function getTaxschemeid(): string|null
    {
        return $this->taxschemeid;
    }

    public function getSupplierAssignedAccountId(): string|null
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
}
