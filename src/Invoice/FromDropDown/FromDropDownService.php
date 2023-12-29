<?php

declare(strict_types=1); 

namespace App\Invoice\FromDropDown;

use App\Invoice\Entity\FromDropDown;

final class FromDropDownService
{
    private FromDropDownRepository $repository;

    public function __construct(FromDropDownRepository $repository)
    {
        $this->repository = $repository;
    }

    public function saveFromDropDown(FromDropDown $model, array $array): void
    {
      isset($array['email']) ? $model->setEmail((string)$array['email']) : '';
      $model->setInclude($array['include'] === '1' ? true : false);
      $model->setDefault_email($array['default_email'] === '1' ? true : false);
      $this->repository->save($model);
    }
    
    public function deleteFromDropDown(FromDropDown $model): void
    {
        $this->repository->delete($model);
    }
}