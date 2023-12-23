<?php

declare(strict_types=1); 

namespace App\Invoice\CustomField;

use App\Invoice\Entity\CustomField;


final class CustomFieldService
{

    private CustomFieldRepository $repository;

    public function __construct(CustomFieldRepository $repository)
    {
        $this->repository = $repository;
    }
    
    /**
     * @param CustomField $model
     * @param array $array
     * @return void
     */
    public function saveCustomField(CustomField $model, array $array): void
    {
       isset($array['table']) ? $model->setTable((string)$array['table']) : '';
       isset($array['label']) ? $model->setLabel((string)$array['label']) : '';
       isset($array['type']) ? $model->setType((string)$array['type']) : '';
       isset($array['location']) ? $model->setLocation((int)$array['location']) : '';
       isset($array['order']) ? $model->setOrder((int)$array['order']) : '';
       $this->repository->save($model);
    }
    
    /**
     * 
     * @param CustomField $model
     * @return void
     */
    public function deleteCustomField(CustomField $model): void
    {
        $this->repository->delete($model);
    }
}