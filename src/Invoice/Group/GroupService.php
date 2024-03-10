<?php

declare(strict_types=1); 

namespace App\Invoice\Group;

use App\Invoice\Entity\Group;


final class GroupService
{

    private GroupRepository $repository;

    /**
     * 
     * @param GroupRepository $repository
     */
    public function __construct(GroupRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * @param Group $model
     * @param array $array
     * @return void
     */
    public function saveGroup(Group $model, array $array): void
    {
       isset($array['name']) ? $model->setName((string)$array['name']) : 'Name';
       isset($array['identifier_format']) ? $model->setIdentifier_format((string)$array['identifier_format']) : 'AAA{{{id}}}';
       isset($array['next_id']) ? $model->setNext_id((int)$array['next_id']) : 0;
       isset($array['left_pad']) ? $model->setLeft_pad((int)$array['left_pad']) : 0;
       $this->repository->save($model);
    }
    
    /**
     * 
     * @param Group $model
     * @return void
     */
    public function deleteGroup(Group $model): void
    {
        // The first three default groups i.e. quote, salesorder, and invoice cannot be deleted
        if (($model->getName() <> 'Quote Group') 
                && ($model->getName() <> 'Invoice Group')
                    && ($model->getName() <> 'Sales Order Group')) {
            $this->repository->delete($model);
        }
    }
}