<?php

declare(strict_types=1); 

namespace App\Invoice\Profile;

use App\Invoice\Entity\Profile;
use App\Invoice\Profile\ProfileRepository;


final class ProfileService
{

    private ProfileRepository $repository;

    public function __construct(ProfileRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * @param Profile $model
     * @param array $array
     * @return void
     */
    public function saveProfile(Profile $model, array $array): void
    {
       isset($array['company_id']) ? $model->setCompany_id((int)$array['company_id']) : '';
       $model->setCurrent($array['current'] === '1' ? 1 : 0);
       isset($array['mobile']) ? $model->setMobile((string)$array['mobile']) : '';
       isset($array['email']) ? $model->setEmail((string)$array['email']) : '';
       isset($array['description']) ? $model->setDescription((string)$array['description']) : '';
       
       $this->repository->save($model);
    }
    
    /**
     * @param Profile $model
     * @return void
     */
    public function deleteProfile(Profile $model): void
    {
        $this->repository->delete($model);
    }
}