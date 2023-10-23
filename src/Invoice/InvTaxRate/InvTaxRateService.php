<?php
declare(strict_types=1); 

namespace App\Invoice\InvTaxRate;

use App\Invoice\Entity\InvTaxRate;

final class InvTaxRateService
{
    private InvTaxRateRepository $repository;

    public function __construct(InvTaxRateRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * 
     * @param InvTaxRate $model
     * @param InvTaxRateForm $form
     * @return void
     */
    public function saveInvTaxRate(InvTaxRate $model, InvTaxRateForm $form): void
    {        
        $inv_id = ((null !==($form->getInv_id())) ? $form->getInv_id() : '');
        // The form is required to have a tax value even if it is a zero rate
        $model->setInv_id((int)$inv_id);
        $tax_rate_id = ((null !==($form->getTax_rate_id())) ? $form->getTax_rate_id() : '');
        // The form is required to have a tax value even if it is a zero rate
        $model->setTax_rate_id((int)$tax_rate_id);
        $model->setInclude_item_tax((int)$form->getInclude_item_tax());
        
        if (null!==$form->getInv_tax_rate_amount()) {
            $model->setInv_tax_rate_amount($form->getInv_tax_rate_amount()); 
        }
        
        $this->repository->save($model);
    }
    
    /**
     * @param null|string $new_inv_id
     */
    public function initializeCreditInvTaxRate(int $basis_inv_id, string|null $new_inv_id) : void
    {
        $basis_invoice_tax_rates = $this->repository->repoInvquery((string)$basis_inv_id);
        /** @var InvTaxRate $basis_invoice_tax_rate */
        foreach ($basis_invoice_tax_rates as $basis_invoice_tax_rate) {
            $new_invoice_tax_rate = new InvTaxRate();
            $new_invoice_tax_rate->setInv_id((int)$new_inv_id);
            $new_invoice_tax_rate->setTax_rate_id((int)$basis_invoice_tax_rate->getTax_rate_id());
            if (($basis_invoice_tax_rate->getInclude_item_tax() ==1||($basis_invoice_tax_rate->getInclude_item_tax()==0))) {
                $new_invoice_tax_rate->setInclude_item_tax($basis_invoice_tax_rate->getInclude_item_tax() ?: 0);            
            }
            $new_invoice_tax_rate->setInv_tax_rate_amount(($basis_invoice_tax_rate->getInv_tax_rate_amount() ?: 0.00)*-1); 
            $this->repository->save($new_invoice_tax_rate);
        }
    }
    
    /**
     * 
     * @param array|InvTaxRate|null $model
     * @return void
     */
    public function deleteInvTaxRate(array|InvTaxRate|null $model): void
    {
        $this->repository->delete($model);
    }
}