<?php

declare(strict_types=1);

namespace App\Invoice\ClientPeppol;

use App\Invoice\Entity\ClientPeppol;

final readonly class ClientPeppolService
{
    public function __construct(private ClientPeppolRepository $repository)
    {
    }

    public function saveClientPeppol(ClientPeppol $model, array $array): void
    {
        isset($array['id']) ? $model->setId((int) $array['id']) : '';
        isset($array['client_id']) ? $model->setClient_id((int) $array['client_id']) : '';
        isset($array['accounting_cost']) ? $model->setAccountingCost((string) $array['accounting_cost']) : '';
        isset($array['buyer_reference']) ? $model->setBuyerReference((string) $array['buyer_reference']) : '';
        isset($array['endpointid']) ? $model->setEndpointid((string) $array['endpointid']) : '';
        isset($array['endpointid_schemeid']) ? $model->setEndpointid_schemeid((string) $array['endpointid_schemeid']) : '';
        isset($array['financial_institution_branchid']) ? $model->setFinancial_institution_branchid((string) $array['financial_institution_branchid']) : '';
        isset($array['identificationid']) ? $model->setIdentificationid((string) $array['identificationid']) : '';
        isset($array['identificationid_schemeid']) ? $model->setIdentificationid_schemeid((string) $array['identificationid_schemeid']) : '';
        isset($array['legal_entity_companyid']) ? $model->setLegal_entity_companyid((string) $array['legal_entity_companyid']) : '';
        isset($array['legal_entity_companyid_schemeid']) ? $model->setLegal_entity_companyid_schemeid((string) $array['legal_entity_companyid_schemeid']) : '';
        isset($array['legal_entity_company_legal_form']) ? $model->setLegal_entity_company_legal_form((string) $array['legal_entity_company_legal_form']) : '';
        isset($array['legal_entity_registration_name']) ? $model->setLegal_entity_registration_name((string) $array['legal_entity_registration_name']) : '';
        isset($array['supplierassignedaccountid']) ? $model->setSupplierAssignedAccountId((string) $array['legal_entity_registration_name']) : '';
        isset($array['taxschemecompanyid']) ? $model->setTaxschemecompanyid((string) $array['taxschemecompanyid']) : '';
        isset($array['taxschemeid']) ? $model->setTaxschemeid((string) $array['taxschemeid']) : '';
        $this->repository->save($model);
    }

    public function deleteClientPeppol(ClientPeppol $model): void
    {
        $this->repository->delete($model);
    }
}
