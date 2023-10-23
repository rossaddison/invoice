<?php

declare(strict_types=1); 

namespace App\Invoice\ClientPeppol;

use App\Invoice\Entity\ClientPeppol;

final class ClientPeppolService
{

    private ClientPeppolRepository $repository;

    public function __construct(ClientPeppolRepository $repository)
    {
        $this->repository = $repository;
    }

    public function saveClientPeppol(ClientPeppol $model, ClientPeppolForm $form): void
    {
        null!==$form->getId() ? $model->setId($form->getId()) : '';
        null!==$form->getClient_id() ? $model->setClient_id($form->getClient_id()) : '';
        null!==$form->getAccounting_cost() ? $model->setAccountingCost($form->getAccounting_cost()) : '';
        null!==$form->getBuyer_reference() ? $model->setBuyerReference($form->getBuyer_reference()) : '';
        null!==$form->getEndpointid() ? $model->setEndpointid($form->getEndpointid()) : '';
        null!==$form->getEndpointid_schemeid() ? $model->setEndpointid_schemeid($form->getEndpointid_schemeid()) : '';
        null!==$form->getFinancial_institution_branchid() ? $model->setFinancial_institution_branchid($form->getFinancial_institution_branchid()) : '';
        null!==$form->getIdentificationid() ? $model->setIdentificationid($form->getIdentificationid()) : '';
        null!==$form->getIdentificationid_schemeid() ? $model->setIdentificationid_schemeid($form->getIdentificationid_schemeid()) : '';
        null!==$form->getLegal_entity_companyid() ? $model->setLegal_entity_companyid($form->getLegal_entity_companyid()) : '';
        null!==$form->getLegal_entity_companyid_schemeid() ? $model->setLegal_entity_companyid_schemeid($form->getLegal_entity_companyid_schemeid()) : '';
        null!==$form->getLegal_entity_company_legal_form() ? $model->setLegal_entity_company_legal_form($form->getLegal_entity_company_legal_form()) : '';
        null!==$form->getLegal_entity_registration_name() ? $model->setLegal_entity_registration_name($form->getLegal_entity_registration_name()) : '';
        null!==$form->getSupplierAssignedAccountId() ? $model->setSupplierAssignedAccountId($form->getSupplierAssignedAccountId()) : '';
        null!==$form->getTaxschemecompanyid() ? $model->setTaxschemecompanyid($form->getTaxschemecompanyid()) : '';
        null!==$form->getTaxschemeid() ? $model->setTaxschemeid($form->getTaxschemeid()) : '';
        $this->repository->save($model);
    }
    
    public function deleteClientPeppol(ClientPeppol $model): void
    {
        $this->repository->delete($model);
    }
}