<?php

declare(strict_types=1);

namespace App\Invoice\ClientPeppol;

use App\Invoice\Entity\ClientPeppol;
use App\Invoice\Client\ClientRepository as CR;

final readonly class ClientPeppolService
{
    public function __construct(
        private ClientPeppolRepository $repository,
        private CR $cR,
    ) {
    }

    public function saveClientPeppol(
        ClientPeppol $model,
        array $array
    ): void {
        $this->persist($model, $array);
        isset($array['id']) ?
            $model->setId((int) $array['id']) : '';
        isset($array['client_id']) ?
            $model->setClientId((int) $array['client_id']) : '';
        isset($array['accounting_cost']) ?
            $model->setAccountingCost(
                (string) $array['accounting_cost']) : '';
        isset($array['buyer_reference']) ?
            $model->setBuyerReference(
                (string) $array['buyer_reference']) : '';
        isset($array['endpointid']) ?
            $model->setEndpointid(
                (string) $array['endpointid']) : '';
        isset($array['endpointid_schemeid']) ?
            $model->setEndpointidSchemeid(
                (string) $array['endpointid_schemeid']) : '';
        isset($array['financial_institution_branchid']) ?
            $model->setFinancialInstitutionBranchid(
                (string) $array['financial_institution_branchid'])
            : '';
        isset($array['identificationid']) ?
            $model->setIdentificationid(
                (string) $array['identificationid']) : '';
        isset($array['identificationid_schemeid']) ?
            $model->setIdentificationidSchemeid(
                (string) $array['identificationid_schemeid']) : '';
        isset($array['legal_entity_companyid']) ?
            $model->setLegalEntityCompanyid(
                (string) $array['legal_entity_companyid']) : '';
        isset($array['legal_entity_companyid_schemeid']) ?
            $model->setLegalEntityCompanyidSchemeid(
                (string) $array['legal_entity_companyid_schemeid'])
            : '';
        isset($array['legal_entity_company_legal_form']) ?
            $model->setLegalEntityCompanyLegalForm(
                (string) $array['legal_entity_company_legal_form'])
            : '';
        isset($array['legal_entity_registration_name']) ?
            $model->setLegalEntityRegistrationName(
                (string) $array['legal_entity_registration_name'])
            : '';
        isset($array['supplier_assigned_accountid']) ?
            $model->setSupplierAssignedAccountId(
                (string) $array['supplier_assigned_accountid'])
            : '';
        isset($array['taxschemecompanyid']) ?
            $model->setTaxschemecompanyid(
                (string) $array['taxschemecompanyid']) : '';
        isset($array['taxschemeid']) ?
            $model->setTaxschemeid(
                (string) $array['taxschemeid']) : '';
        $this->repository->save($model);
    }

    private function persist(
        ClientPeppol $model,
        array $array
    ): void {
        $client = 'client_id';
        if (isset($array[$client])) {
            $model->setClient(
                $this->cR->repoClientquery((int) $array[$client]));
        }
    }

    public function deleteClientPeppol(ClientPeppol $model): void
    {
        $this->repository->delete($model);
    }
}
