<?php

declare(strict_types=1); 

namespace App\Invoice\Payment;

use App\Invoice\Entity\Payment;
use App\Invoice\Setting\SettingRepository;

final class PaymentService
{

    private PaymentRepository $repository;
    private SettingRepository $sR;

    public function __construct(PaymentRepository $repository, SettingRepository $sR)
    {
        $this->repository = $repository;
        $this->sR = $sR;
    }
    
    /**
     * 
     * @param Payment $model
     * @param PaymentForm $form
     * @return void
     */
    public function addPayment(Payment $model, PaymentForm $form): void
    {
       $form->getPayment_method_id() ? $model->setPayment_method_id($form->getPayment_method_id()) : '';
       $model->setPayment_date($form->getPayment_date($this->sR));
       $form->getAmount() ? $model->setAmount($form->getAmount()) : '';
       $form->getNote() ? $model->setNote($form->getNote()) : '';
       $form->getInv_id() ? $model->setInv_id($form->getInv_id()) : ''; 
       $this->repository->save($model);
    }
    
    /**
     * 
     * @param Payment $model
     * @param array $array
     * @return void
     */
    public function addPayment_via_payment_handler(Payment $model, array $array): void
    {
       $model->setPayment_method_id((int)$array['payment_method_id']);
       /** @var \DateTime $array['payment_date'] */
       $model->setPayment_date($array['payment_date']);
       /** @var float $array['amount'] */
       $model->setAmount($array['amount']);
       /** @var string $array['note'] */
       $model->setNote($array['note']);
       $model->setInv_id((int)$array['inv_id']); 
       $this->repository->save($model);
    }
    
    /**
     * 
     * @param Payment $model
     * @param PaymentForm $form
     * @return void
     */
    public function editPayment(Payment $model, PaymentForm $form): void
    {
       $model->setPayment_date($form->getPayment_date($this->sR));
       $form->getAmount() ? $model->setAmount($form->getAmount()) : '';
       $form->getNote() ? $model->setNote($form->getNote()) : '';
       // If the payment is to be allocated against another invoice ie. inv_id changed
       // then initialize and set relation ie. setInv to null otherwise if  form id ie. $form->getInv_id has 
       // not changed from old id ie. $model->getInv()->getId() then use relation as is ie. 
       // $model->setInv($model->getInv())
       null!==$form->getInv_id() ? $model->setInv($model->getInv()?->getId() 
                                 == $form->getInv_id() 
                                 ? $model->getInv() 
                                 : null)
                                 // do nothing
                                 : '';
       
       $model->setInv_id((int)$form->getInv_id()); 
       
       null!==$form->getPayment_method_id() ? $model->setPaymentMethod($model->getPaymentMethod()?->getId() 
                                            == $form->getPayment_method_id() 
                                            ? $model->getPaymentMethod() 
                                            : null)
                                            : '';
       $model->setPayment_method_id((int)$form->getPayment_method_id());
       
       $this->repository->save($model);
    }
    
    /**
     * 
     * @param Payment $model
     * @return void
     */
    public function deletePayment(Payment $model): void
    {
        $this->repository->delete($model);
    }
}