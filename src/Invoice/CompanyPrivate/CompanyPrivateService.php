<?php

declare(strict_types=1); 

namespace App\Invoice\CompanyPrivate;

use App\Invoice\Entity\CompanyPrivate;
use App\Invoice\Setting\SettingRepository;


final class CompanyPrivateService
{

    private CompanyPrivateRepository $repository;

    public function __construct(CompanyPrivateRepository $repository)
    {
        $this->repository = $repository;
    }
    
    /**
     * 
     * @param CompanyPrivate $model
     * @param CompanyPrivateForm $form
     * @param SettingRepository $s
     * @return void
     */
    public function saveCompanyPrivate(CompanyPrivate $model, CompanyPrivateForm $form, SettingRepository $s): void
    {
       //null!==$form->getCompany_id() ? $model->setCompany($model->getCompany()->getId() == $form->getCompany_id() ? $model->getCompany() : null): ''; 
       null!==$form->getCompany_id() ? $model->setCompany_id($form->getCompany_id()) : '';
       null!==$form->getVat_id() ? $model->setVat_id($form->getVat_id()) : '';
       null!==$form->getTax_code() ? $model->setTax_code($form->getTax_code()) : '';
       null!==$form->getIban() ? $model->setIban($form->getIban()) : '';
       null!==$form->getGln() ? $model->setGln($form->getGln()) : '';
       null!==$form->getRcc() ? $model->setRcc($form->getRcc()) : '';
       null!==$form->getLogo_filename() ? $model->setLogo_filename($form->getLogo_filename()) : '';
       $model->setStart_date($form->getStart_date($s));
       $model->setEnd_date($form->getEnd_date($s));
       $this->repository->save($model);
    }
    
    /**
     * 
     * @param CompanyPrivate $model
     * @param CompanyPrivateForm $form
     * @param SettingRepository $s
     * @return void
     */
    public function addCompanyPrivate(CompanyPrivate $model, CompanyPrivateForm $form, SettingRepository $s): void
    {
       null!==$form->getCompany_id() ? $model->setCompany_id($form->getCompany_id()) : '';
       null!==$form->getVat_id() ? $model->setVat_id($form->getVat_id()) : '';
       null!==$form->getTax_code() ? $model->setTax_code($form->getTax_code()) : '';
       null!==$form->getIban() ? $model->setIban($form->getIban()) : '';
       null!==$form->getGln() ? $model->setGln($form->getGln()) : '';
       null!==$form->getRcc() ? $model->setRcc($form->getRcc()) : '';
       null!==$form->getLogo_filename() ? $model->setLogo_filename($form->getLogo_filename()) : '';
       $model->setStart_date($form->getStart_date($s));
       $model->setEnd_date($form->getEnd_date($s));
       $this->repository->save($model);
    }
    
    /**
     * 
     * @param array|CompanyPrivate|null $model
     * @return void
     */
    public function deleteCompanyPrivate(array|CompanyPrivate|null $model): void
    {
        $this->repository->delete($model);
    }
}