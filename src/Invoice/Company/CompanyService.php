<?php

declare(strict_types=1); 

namespace App\Invoice\Company;

use App\Invoice\Entity\Company;


final class CompanyService
{

    private CompanyRepository $repository;

    public function __construct(CompanyRepository $repository)
    {
        $this->repository = $repository;
    }
    
    /**
     * 
     * @param Company $model
     * @param CompanyForm $form
     * @return void
     */
    public function saveCompany(Company $model, CompanyForm $form): void
    {
       null!==$form->getCurrent() ? $model->setCurrent($form->getCurrent()) : '';
       null!==$form->getName() ? $model->setName($form->getName()) : '';
       null!==$form->getAddress_1() ? $model->setAddress_1($form->getAddress_1()) : '';
       null!==$form->getAddress_2() ? $model->setAddress_2($form->getAddress_2()) : '';
       null!==$form->getCity() ? $model->setCity($form->getCity()) : '';
       null!==$form->getState() ? $model->setState($form->getState()) : '';
       null!==$form->getZip() ? $model->setZip($form->getZip()) : '';
       null!==$form->getCountry() ? $model->setCountry($form->getCountry()) : '';
       null!==$form->getPhone() ? $model->setPhone($form->getPhone()) : '';
       null!==$form->getFax() ? $model->setFax($form->getFax()) : '';
       null!==$form->getEmail() ? $model->setEmail($form->getEmail()) : '';
       null!==$form->getWeb() ? $model->setWeb($form->getWeb()) : '';       
       $this->repository->save($model);
    }
    
    /**
     * 
     * @param array|Company|null $model
     * @return void
     */
    public function deleteCompany(array|Company|null $model): void
    {
        $this->repository->delete($model);
    }
}