<?php

declare(strict_types=1); 

namespace App\Invoice\Profile;

use App\Invoice\Entity\Profile;
use App\Invoice\Profile\ProfileForm;
use App\Invoice\Profile\ProfileRepository;


final class ProfileService
{

    private ProfileRepository $repository;

    public function __construct(ProfileRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * 
     * @param Profile $model
     * @param ProfileForm $form
     * @return void
     */
    public function saveProfile(Profile $model, ProfileForm $form): void
    {
       $form->getCompany_id() ? $model->setCompany_id($form->getCompany_id()) : '';
       $form->getCurrent() ? $model->setCurrent($form->getCurrent()) : '';
       $form->getMobile() ? $model->setMobile($form->getMobile()) : '';
       $form->getEmail() ? $model->setEmail($form->getEmail()) : '';
 
       $this->repository->save($model);
    }
    
    /**
     * 
     * @param Profile $model
     * @return void
     */
    public function deleteProfile(Profile $model): void
    {
        $this->repository->delete($model);
    }
}