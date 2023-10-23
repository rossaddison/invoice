<?php

declare(strict_types=1); 

namespace App\Invoice\UserInv;

use App\Invoice\Entity\UserInv;

final class UserInvService
{
    private UserInvRepository $repository;

    public function __construct(UserInvRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * 
     * @param UserInv $model
     * @param UserInvForm $form
     * @return void
     */
    public function saveUserInv(UserInv $model, UserInvForm $form): void
    {        
       $model->setUser_id($form->getUser_id());
       null!==$form->getType() ? $model->setType($form->getType()) : '';
       null!==$form->getActive() ? $model->setActive($form->getActive()) : '';
       null!==$form->getLanguage() ? $model->setLanguage($form->getLanguage()) : '';
       null!==$form->getAll_clients() ? $model->setAll_clients($form->getAll_clients()) : '';
       null!==$form->getName() ? $model->setName($form->getName()) : '';
       null!==$form->getCompany() ? $model->setCompany($form->getCompany()) : '';
       null!==$form->getAddress_1() ? $model->setAddress_1($form->getAddress_1()) : '';
       null!==$form->getAddress_2 () ? $model->setAddress_2($form->getAddress_2()) : '';
       null!==$form->getCity() ? $model->setCity($form->getCity()) : '';
       null!==$form->getState() ? $model->setState($form->getState()) : '';
       null!==$form->getZip() ? $model->setZip($form->getZip()) : '';
       null!==$form->getCountry() ? $model->setCountry($form->getCountry()) : '';
       null!==$form->getPhone() ? $model->setPhone($form->getPhone()) : '';
       null!==$form->getFax() ? $model->setFax($form->getFax()) : '';
       null!==$form->getMobile() ? $model->setMobile($form->getMobile()) : '';
       null!==$form->getEmail() ? $model->setEmail($form->getEmail()) : '';
       null!==$form->getPassword() ? $model->setPassword($form->getPassword()) : '';
       null!==$form->getWeb() ? $model->setWeb($form->getWeb()) : '';
       null!==$form->getVat_id() ? $model->setVat_id($form->getVat_id()) : '';
       null!==$form->getTax_code() ? $model->setTax_code($form->getTax_code()) : '';
       null!==$form->getSalt() ? $model->setSalt($form->getSalt()) : '';
       null!==$form->getPasswordreset_token() ? $model->setPasswordreset_token($form->getPasswordreset_token()) : '';
       null!==$form->getSubscribernumber() ? $model->setSubscribernumber($form->getSubscribernumber()) : '';
       null!==$form->getIban() ? $model->setIban($form->getIban()) : '';
       null!==$form->getGln() ? $model->setGln($form->getGln()) : '';
       null!==$form->getRcc() ? $model->setRcc($form->getRcc()) : '';
       $this->repository->save($model);
    }
    
    /**
     * 
     * @param UserInv $model
     * @return void
     */
    public function deleteUserInv(UserInv $model): void
    {
        $this->repository->delete($model);
    }
}