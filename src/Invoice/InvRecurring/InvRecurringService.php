<?php

declare(strict_types=1); 

namespace App\Invoice\InvRecurring;

use App\Invoice\Entity\InvRecurring;


final class InvRecurringService
{

    private InvRecurringRepository $repository;

    /**
     * 
     * @param InvRecurringRepository $repository
     */
    public function __construct(InvRecurringRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * 
     * @param InvRecurring $model
     * @param InvRecurringForm $form
     * @return void
     */
    public function saveInvRecurring(InvRecurring $model, InvRecurringForm $form): void
    {
       null!==$form->getInv_id() ? $model->setInv_id($form->getInv_id()) : '';
       null!==$form->getStart() ? $model->setStart($form->getStart()) : '';
       null!==$form->getEnd() ? $model->setEnd($form->getEnd()) : '';
       null!==$form->getFrequency() ? $model->setFrequency($form->getFrequency()) : '';
       null!==$form->getNext() ?$model->setNext($form->getNext()) : '';
       $this->repository->save($model);
    }
    
    /**
     * 
     * @param InvRecurring $model
     * @return void
     */
    public function deleteInvRecurring(InvRecurring $model): void
    {
        $this->repository->delete($model);
    }
}