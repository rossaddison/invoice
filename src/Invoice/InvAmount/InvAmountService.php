<?php

declare(strict_types=1); 

namespace App\Invoice\InvAmount;

use App\Invoice\Entity\InvAmount;


final class InvAmountService
{

    private InvAmountRepository $repository;

    public function __construct(InvAmountRepository $repository)
    {
        $this->repository = $repository;
    }
    
    /**
     * 
     * @param InvAmount $model
     * @param string $inv_id
     * @return void
     */
    public function initializeInvAmount(InvAmount $model, string $inv_id) : void
    {
       $inv_id ? $model->setInv_id((int)$inv_id) : '';
       $model->setSign(1);
       $model->setItem_subtotal(0.00);
       $model->setItem_tax_total(0.00);
       $model->setTax_total(0.00);
       $model->setTotal(0.00); 
       $model->setPaid(0.00);
       $model->setBalance(0.00); 
       $this->repository->save($model);
    }

    /**
     * @param InvAmount $model
     * @param int $basis_inv_id
     * @param string $new_inv_id
     * @return void
     */
    public function initializeCreditInvAmount(InvAmount $model, int $basis_inv_id, string $new_inv_id) : void
    {
       $basis_invoice = $this->repository->repoInvquery($basis_inv_id);
       $new_inv_id ? $model->setInv_id((int)$new_inv_id) : '';
       $model->setSign(1);
       null!==$basis_invoice ? $model->setItem_subtotal(($basis_invoice->getItem_subtotal() ?: 0.00)*-1) : '';
       null!==$basis_invoice ? $model->setItem_tax_total(($basis_invoice->getItem_tax_total() ?: 0.00)*-1) : '';
       null!==$basis_invoice ? $model->setTax_total(($basis_invoice->getTax_total() ?: 0.00)*-1) : '';
       null!==$basis_invoice ? $model->setTotal(($basis_invoice->getTotal() ?: 0.00)*-1) : ''; 
       $model->setPaid(0.00);
       null!==$basis_invoice ? $model->setBalance(($basis_invoice->getBalance()?: 0.00)*-1) : ''; 
       $this->repository->save($model);
    }

    /**
     * 
     * @param InvAmount $model
     * @param int $basis_inv_id
     * @param string $new_inv_id
     * @return void
     */
    public function initializeCopyInvAmount(InvAmount $model, int $basis_inv_id, string $new_inv_id) : void
    {
        $basis_invoice = $this->repository->repoInvquery($basis_inv_id);
        $new_inv_id ? $model->setInv_id((int)$new_inv_id) : '';
        $model->setSign(1);
        /** @psalm-suppress PossiblyNullArgument, PossiblyNullReference */
        $model->setItem_subtotal($basis_invoice->getItem_subtotal());
        $model->setItem_tax_total($basis_invoice->getItem_tax_total() ?: 0.00);
        $model->setTax_total($basis_invoice->getTax_total() ?: 0.00);
        $model->setTotal($basis_invoice->getTotal() ?: 0.00); 
        $model->setPaid(0.00);
        $model->setBalance($basis_invoice->getTotal() ?: 0.00); 
        $this->repository->save($model);
    } 

    /**
     * 
     * @param InvAmount $model
     * @param InvAmountForm $form
     * @return void
     */
    public function saveInvAmount(InvAmount $model, InvAmountForm $form): void
    {  
       $form->getInv_id() ? $model->setInv_id($form->getInv_id()) : '';
       $model->setSign(1);
       $form->getItem_subtotal() ? $model->setItem_subtotal($form->getItem_subtotal()) : '';
       $form->getItem_tax_total() ? $model->setItem_tax_total($form->getItem_tax_total()) : '';
       $form->getTax_total() ? $model->setTax_total($form->getTax_total()) : '';
       $form->getTotal() ? $model->setTotal($form->getTotal()) : ''; 
       $form->getPaid() ? $model->setPaid($form->getPaid()) : '';
       $form->getBalance() ? $model->setBalance($form->getBalance()) : ''; 
       $this->repository->save($model);
    }
    
    /**
     * 
     * @param InvAmount $model
     * @param array $array
     * @return void
     */
    public function saveInvAmountViaCalculations(InvAmount $model, array  $array): void
    {        
       $model->setInv_id((int)$array['inv_id']);
       $model->setItem_subtotal((float)$array['item_subtotal']);
       $model->setItem_tax_total((float)$array['item_taxtotal']);
       $model->setTax_total((float)$array['tax_total']);
       $model->setTotal((float)$array['total']);
       $model->setPaid((float)$array['paid']);
       $model->setBalance((float)$array['balance']);
       $this->repository->save($model);
    }
    
    /**
     * 
     * @param InvAmount $model
     * @return void
     */
    public function deleteInvAmount(InvAmount $model): void
    {
        $this->repository->delete($model);
    }
}