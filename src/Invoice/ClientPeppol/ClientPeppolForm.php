<?php

declare(strict_types=1);

namespace App\Invoice\ClientPeppol;

use App\Invoice\Entity\ClientPeppol;
use Yiisoft\FormModel\FormModel;
use Yiisoft\Validator\Rule\Required;

final class ClientPeppolForm extends FormModel
{
    private ?int $id        = null;
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
        $this->id                              = (int) $client_peppol->getId();
        $this->client_id                       = (int) $client_peppol->getClient_id();
        $this->accounting_cost                 = $client_peppol->getAccountingCost();
        $this->buyer_reference                 = $client_peppol->getBuyerReference();
        $this->endpointid                      = $client_peppol->getEndpointid();
        $this->endpointid_schemeid             = $client_peppol->getEndpointid_schemeid();
        $this->financial_institution_branchid  = $client_peppol->getFinancial_institution_branchid();
        $this->identificationid                = $client_peppol->getIdentificationid();
        $this->identificationid_schemeid       = $client_peppol->getIdentificationid_schemeid();
        $this->legal_entity_registration_name  = $client_peppol->getLegal_entity_registration_name();
        $this->legal_entity_companyid          = $client_peppol->getLegal_entity_companyid();
        $this->legal_entity_companyid_schemeid = $client_peppol->getLegal_entity_companyid_schemeid();
        $this->legal_entity_company_legal_form = $client_peppol->getLegal_entity_company_legal_form();
        $this->taxschemecompanyid              = $client_peppol->getTaxschemecompanyid();
        $this->taxschemeid                     = $client_peppol->getTaxschemeid();
        $this->supplier_assigned_accountid     = $client_peppol->getSupplierAssignedAccountId();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getClient_id(): ?int
    {
        return $this->client_id;
    }

    public function getAccounting_cost(): ?string
    {
        return $this->accounting_cost;
    }

    public function getBuyer_reference(): ?string
    {
        return $this->buyer_reference;
    }

    public function getEndpointid(): ?string
    {
        return $this->endpointid;
    }

    public function getEndpointid_schemeid(): ?string
    {
        return $this->endpointid_schemeid;
    }

    public function getFinancial_institution_branchid(): ?string
    {
        return $this->financial_institution_branchid;
    }

    public function getIdentificationid(): ?string
    {
        return $this->identificationid;
    }

    public function getIdentificationid_schemeid(): ?string
    {
        return $this->identificationid_schemeid;
    }

    public function getLegal_entity_registration_name(): ?string
    {
        return $this->legal_entity_registration_name;
    }

    public function getLegal_entity_companyid(): ?string
    {
        return $this->legal_entity_companyid;
    }

    public function getLegal_entity_companyid_schemeid(): ?string
    {
        return $this->legal_entity_companyid_schemeid;
    }

    public function getLegal_entity_company_legal_form(): ?string
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
     * @psalm-return ''
     */
    #[\Override]
    public function getFormName(): string
    {
        return '';
    }
}
