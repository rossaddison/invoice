<?php

declare(strict_types=1); 

namespace App\Invoice\PaymentMethod;

use App\Invoice\Entity\PaymentMethod;

final class PaymentMethodService
{

    private PaymentMethodRepository $repository;

    public function __construct(PaymentMethodRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * 
     * @param PaymentMethod $model
     * @param PaymentMethodForm $form
     * @return void
     */
    public function savePaymentMethod(PaymentMethod $model, PaymentMethodForm $form): void
    {
        
       $form->getName() ? $model->setName($form->getName()) : '';
 
       $this->repository->save($model);
    }
    
    /**
     * 
     * @param PaymentMethod $model
     * @return void
     */
    public function deletePaymentMethod(PaymentMethod $model): void
    {
        $this->repository->delete($model);
    }
}