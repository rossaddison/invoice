<?php

declare(strict_types=1); 

namespace App\Invoice\PaymentPeppol;

use App\Invoice\Entity\PaymentPeppol;


final class PaymentPeppolService
{

    private PaymentPeppolRepository $repository;

    public function __construct(PaymentPeppolRepository $repository)
    {
        $this->repository = $repository;
    }

    public function savePaymentPeppol(PaymentPeppol $model, PaymentPeppolForm $form): void
    {
        null!==$form->getInv_id() ? $model->setInv_id($form->getInv_id()) : '';
        null!==$form->getProvider() ? $model->setProvider($form->getProvider()) : '';
        $this->repository->save($model);
    }
    
    public function deletePaymentPeppol(PaymentPeppol $model): void
    {
        $this->repository->delete($model);
    }
}