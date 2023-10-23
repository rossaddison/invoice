<?php
declare(strict_types=1); 

namespace App\Invoice\InvItem;

use App\Invoice\Entity\InvItem;
use App\Invoice\Entity\InvItemAmount;
use App\Invoice\Entity\Task;
use App\Invoice\InvItemAmount\InvItemAmountRepository as IIAR;
use App\Invoice\InvItemAmount\InvItemAmountService as IIAS;
use App\Invoice\Product\ProductRepository as PR;
use App\Invoice\Setting\SettingRepository as SR;
use App\Invoice\Task\TaskRepository as taskR;
use App\Invoice\TaxRate\TaxRateRepository as TRR;
use App\Invoice\Unit\UnitRepository as UNR;

final class InvItemService
{
    private InvItemRepository $repository;
 
    public function __construct(InvItemRepository $repository)
    {
        $this->repository = $repository;
    }
    
    /**
     * 
     * @param InvItem $model
     * @param InvItemForm $form
     * @param string $inv_id
     * @param PR $pr
     * @param TRR $trr
     * @param IIAS $iias
     * @param IIAR $iiar
     * @param SR $s
     * @param UNR $unR
     * @return void
     */
    public function addInvItem_product(InvItem $model, InvItemForm $form, string $inv_id,PR $pr, TRR $trr , IIAS $iias, IIAR $iiar, SR $s, UNR $unR): void
    {        
       // This function is used in product/save_product_lookup_item_product when adding a product using the modal 
       $tax_rate_id = ((null !==($form->getTax_rate_id())) ? $form->getTax_rate_id() : '');
       // The form is required to have a tax value even if it is a zero rate
       $model->setTax_rate_id((int)$tax_rate_id);
       $model->setInv_id((int)$inv_id);
       $so_item_id = ((null !==($form->getSo_item_id())) ? $form->getSo_item_id() : '');
       $model->setSo_item_id((int)$so_item_id);
       $product_id = ((null !==($form->getProduct_id())) ? $form->getProduct_id() : '');
       $model->setProduct_id((int)$product_id);       
       $product = $pr->repoProductquery($form->getProduct_id());
       if ($product) {
        $name = (( (null !==($form->getProduct_id())) && ($pr->repoCount($product_id)> 0) ) ? $product->getProduct_name() : '');  
                
        $model->setName($name ?? '');

        // If the user has changed the description on the form => override default product description
        $description = ((null !==($form->getDescription())) ? 
                                  $form->getDescription() : 
                                  $product->getProduct_description());
        $model->setDescription($description ?: '');
        $note = ((null !==($form->getNote())) ? $form->getNote() : '');
        $model->setNote($note ?: '');
       }
       $model->setQuantity($form->getQuantity() ?: 1);
       
       $model->setPrice($form->getPrice() ?: 0.00);
       $model->setDiscount_amount($form->getDiscount_amount() ?: 0.00);
       $model->setOrder($form->getOrder() ?: 0);
              
       $model->setDate(new \DateTimeImmutable('now'));
       
       // Product_unit is a string which we get from unit's name field using the unit_id
       $unit = $unR->repoUnitquery((string)$form->getProduct_unit_id());
       if ($unit) {
          $model->setProduct_unit($unit->getUnit_name());
       }     
       $model->setProduct_unit_id((int)$form->getProduct_unit_id());
       $tax_rate_percentage = $this->taxrate_percentage((int)$tax_rate_id, $trr);
       // Users are required to enter a tax rate even if it is zero percent.
              
       $model->setBelongs_to_vat_invoice((int)($s->get_setting('enable_vat_registration') ?: '0'));
       if ($product_id) {
         $this->repository->save($model);
         if (null!==$form->getQuantity() && null!==$form->getPrice() && null!==$form->getDiscount_amount() && null!==$tax_rate_percentage) {
            $this->saveInvItemAmount((int)$model->getId(), $form->getQuantity(), $form->getPrice(), $form->getDiscount_amount(), 0.00, 0.00,  $tax_rate_percentage, $iias, $iiar, $s);
         }
       }  
    }
    
    /**
     * 
     * @param InvItem $model
     * @param InvItemForm $form
     * @param string $inv_id
     * @param PR $pr
     * @param SR $s
     * @param UNR $unR
     * @return int
     */
    public function saveInvItem_product(InvItem $model, InvItemForm $form, string $inv_id,PR $pr, SR $s, UNR $unR): int
    {        
       // This function is used in invitem/edit_product when editing an item on the inv view
       // see https://github.com/cycle/orm/issues/348
       null!==$form->getTax_rate_id() ? $model->setTaxRate($model->getTaxRate()?->getTax_rate_id() == $form->getTax_rate_id() ? $model->getTaxRate() : null): '';
       $tax_rate_id = ((null !==($form->getTax_rate_id())) ? $form->getTax_rate_id() : '');
       $model->setTax_rate_id((int)$tax_rate_id);
       
       null!==$form->getProduct_id() ? $model->setProduct($model->getProduct()?->getProduct_id() == $form->getProduct_id() ? $model->getProduct() : null): '';
       $product_id = ((null !==($form->getProduct_id())) ? $form->getProduct_id() : '');
       $model->setProduct_id((int)$product_id);
       
       $model->setInv_id((int)$inv_id);
       
       $so_item_id = ((null !==($form->getSo_item_id())) ? $form->getSo_item_id() : '');
       $model->setSo_item_id((int)$so_item_id);
       
       $product = $pr->repoProductquery($form->getProduct_id());
       if ($product) {
        $name = (( (null !==($form->getProduct_id())) && ($pr->repoCount($product_id)> 0) ) ? $product->getProduct_name() : '');  
        $model->setName($name ?: '');
        // If the user has changed the description on the form => override default product description
        $description = ((null !==($form->getDescription())) ? 
                                 $form->getDescription() : 
                                 $product->getProduct_description());
        $model->setDescription($description ?: '');
        $note = ((null !==($form->getNote())) ? $form->getNote() : '');
        $model->setNote($note ?: '');
       }
       $model->setQuantity($form->getQuantity() ?: 1);
       
       $model->setPrice($form->getPrice() ?: 0.00);
       $model->setDiscount_amount($form->getDiscount_amount() ?: 0.00);
       $model->setOrder($form->getOrder() ?: 0);
       
       $datetimeimmutable = new \DateTimeImmutable('now');
       $model->setDate($datetimeimmutable);
       
       // Product_unit is a string which we get from unit's name field using the unit_id
       $unit = $unR->repoUnitquery((string)$form->getProduct_unit_id());
       if ($unit) {
          $model->setProduct_unit($unit->getUnit_name());
       } 
       $model->setProduct_unit_id((int)$form->getProduct_unit_id());         
       $product_id ? $this->repository->save($model) : '';  
       return (int)$tax_rate_id;
    }
    
    /**
     * @param InvItem $model
     * @param InvItemForm $form
     * @param string $inv_id
     * @param taskR $taskR
     * @param TRR $trr
     * @param IIAS $iias
     * @param IIAR $iiar
     * @param SR $s
     * @return void
     */
    public function addInvItem_task(InvItem $model, InvItemForm $form, string $inv_id, taskR $taskR, TRR $trr , IIAS $iias, IIAR $iiar, SR $s): void
    {        
       // This function is used in task/selection_inv when adding a new task from the modal
       // see https://github.com/cycle/orm/issues/348
       $tax_rate_id = ((null !==($form->getTax_rate_id())) ? $form->getTax_rate_id() : '');
       $model->setTax_rate_id((int)$tax_rate_id);      
       $task_id = ((null !==($form->getTask_id())) ? $form->getTask_id() : '');
       // Product id and task id are mutually exclusive
       $model->setTask_id((int)$task_id);
       
       $model->setInv_id((int)$inv_id);
       
       /** @var Task $task */
       $task = $taskR->repoTaskquery((string)$form->getTask_id());
       $model->setName($task->getName() ?? '');
       
       // If the user has changed the description on the form => override default task description
       $description = '';
       if (null!==$form->getDescription()) {
              $description = $form->getDescription();
       } else {
              $description = $task->getDescription();
       }
       $model->setDescription($description ?: '');
       $note = ((null !==($form->getNote())) ? $form->getNote() : '');
       $model->setNote($note ?: '');
       
       $model->setQuantity($form->getQuantity() ?: 1.00);
       $model->setProduct_unit('');
       $model->setPrice($form->getPrice() ?: 0.00);
       $model->setDiscount_amount($form->getDiscount_amount() ?: 0.00);
       $model->setOrder($form->getOrder() ?: 0);
       
       $datetimeimmutable = new \DateTimeImmutable('now');
       $model->setDate($datetimeimmutable);              
       $tax_rate_percentage = $this->taxrate_percentage((int)$tax_rate_id, $trr);
       if ($task_id) {
            $this->repository->save($model);                
            if (null!==$form->getQuantity() && null!==$form->getPrice() && null!==$form->getDiscount_amount() && null!==$tax_rate_percentage) {
                $this->saveInvItemAmount((int)$model->getId(), $form->getQuantity(), $form->getPrice(), $form->getDiscount_amount(), 0.00, 0.00, $tax_rate_percentage, $iias, $iiar, $s);
            }    
       }
    }
    
    /**
     * @param InvItem $model
     * @param InvItemForm $form
     * @param string $inv_id
     * @param taskR $taskR
     * @param SR $s
     * @return int
     */
    public function saveInvItem_task(InvItem $model, InvItemForm $form, string $inv_id, taskR $taskR, SR $s): int
    {        
       // This function is used in invitem/edit_task when editing an item on the inv view
       // see https://github.com/cycle/orm/issues/348
       null!==$form->getTax_rate_id() ? $model->setTaxRate($model->getTaxRate()?->getTax_rate_id() == $form->getTax_rate_id() ? $model->getTaxRate() : null): '';
       $tax_rate_id = ((null !==($form->getTax_rate_id())) ? $form->getTax_rate_id() : '');
       $model->setTax_rate_id((int)$tax_rate_id);
       
       null!==$form->getTask_id() ? $model->setTask($model->getTask()?->getId() == $form->getTask_id() ? $model->getTask() : null): '';
       $task_id = ((null !==($form->getTask_id())) ? $form->getTask_id() : '');
       // Product id and task id are mutually exclusive
       $model->setTask_id((int)$task_id);
       
       $model->setInv_id((int)$inv_id);
       
       /** @var Task $task */
       $task = $taskR->repoTaskquery((string)$form->getTask_id());
       if (null!==$task->getName()) {
           $model->setName($task->getName() ?? '');
       }
       
       // If the user has changed the description on the form => override default task description
       $description = '';
       if (null!==$form->getDescription()) {
              $description = $form->getDescription();
       } else {
              $description = $task->getDescription();
       }
       $model->setDescription($description ?: '');
       $note = ((null !==($form->getNote())) ? $form->getNote() : '');
       $model->setNote($note ?: '');
       $model->setQuantity($form->getQuantity() ?: 1);
       $model->setProduct_unit('');
       $model->setPrice($form->getPrice() ?: 0.00);
       $model->setDiscount_amount($form->getDiscount_amount() ?: 0.00);
       $model->setOrder($form->getOrder() ?: 0);
              
       $datetimeimmutable = new \DateTimeImmutable('now');
       $model->setDate($datetimeimmutable);
       if ($task_id) {
          $this->repository->save($model);
       }   
       return (int)$tax_rate_id;
    }
    
    /**
     * @param int $inv_item_id
     * @param float $quantity
     * @param float $price
     * @param float $discount
     * @param float $charge_total
     * @param float $allowance_total
     * @param float $tax_rate_percentage
     * @param IIAS $iias
     * @param IIAR $iiar
     * @param SR $s
     * @return void
     */
    public function saveInvItemAmount(int $inv_item_id, float $quantity, float $price, float $discount, float $charge_total, float $allowance_total, float $tax_rate_percentage, IIAS $iias, IIAR $iiar, SR $s): void
    {       
       $iias_array = [];
       $iias_array['inv_item_id'] = $inv_item_id;       
       $sub_total = $quantity * $price;                
       $discount_total = ($quantity * $discount);
       $tax_total = 0.00;
       // NO VAT
       if ($s->get_setting('enable_vat_registration') === '0') { 
           $tax_total = (($sub_total * ($tax_rate_percentage/100)));
       }
       // VAT
       if ($s->get_setting('enable_vat_registration') === '1') { 
            // EARLY SETTLEMENT CASH DISCOUNTS MUST BE REMOVED BEFORE VAT IS DETERMINED
            // @see https://informi.co.uk/finance/how-vat-affected-discounts
            $tax_total = ((($sub_total-$discount_total+$charge_total) * ($tax_rate_percentage/100)));
       }
       $iias_array['discount'] = $discount_total;
       $iias_array['charge'] = $charge_total;
       $iias_array['allowance'] = $allowance_total;
       $iias_array['subtotal'] = $sub_total;
       $iias_array['taxtotal'] = $tax_total;
       $iias_array['total'] = ($sub_total - $discount_total + $charge_total - $allowance_total + $tax_total);       
       
       if ($iiar->repoCount((string)$inv_item_id) === 0) {
         $iias->saveInvItemAmountNoForm(new InvItemAmount(), $iias_array);} else {
         $inv_item_amount = $iiar->repoInvItemAmountquery((string)$inv_item_id);    
         if ($inv_item_amount) {
            $iias->saveInvItemAmountNoForm($inv_item_amount, $iias_array);
         }
       }                      
    }        
    
    /**
     * 
     * @param InvItem $model
     * @return void
     */
    public function deleteInvItem(InvItem $model): void 
    {
        $this->repository->delete($model);
    }
    
    /**
     * 
     * @param int $id
     * @param TRR $trr
     * @return float|null
     */
    public function taxrate_percentage(int $id, TRR $trr): float|null
    {
        $taxrate = $trr->repoTaxRatequery((string)$id);
        if ($taxrate) {
            $percentage = $taxrate->getTax_rate_percent();        
            return $percentage;
        }
        return null;
    }
    
    /**
     * 
     * @param int $basis_inv_id
     * @param string $new_inv_id
     * @param InvItemRepository $iiR
     * @param IIAR $iiaR
     * @param SR $sR
     * @return void
     */
    public function initializeCreditInvItems(int $basis_inv_id, string $new_inv_id, InvItemRepository $iiR, IIAR $iiaR, SR $sR): void {        
        // Get the basis invoice's items and balance with a negative quantity
        $items = $iiR->repoInvquery((string)$basis_inv_id);
        /** @var InvItem $item */
        foreach ($items as $item){
            $new_item = new InvItem();
            $new_item->setInv_id((int)$new_inv_id);
            $new_item->setTax_rate_id((int)$item->getTax_rate_id());
            $item->getProduct_id() ? $new_item->setProduct_id((int)$item->getProduct_id()) 
            : $new_item->setTask_id((int)$item->getTask_id()); 
            $new_item->setName($item->getName() ?? '');
            $new_item->setDescription($item->getDescription() ?? '');
            $new_item->setNote($item->getNote() ?? '');
            $new_item->setQuantity(($item->getQuantity()?: 1)*-1);
            $new_item->setPrice($item->getPrice() ?? 0.00);
            $new_item->setDiscount_amount($item->getDiscount_amount() ?? 0.00);
            // TODO Ordering of items
            $new_item->setOrder(0);
            // Even if an invoice is balanced with a credit invoice it will remain recurring ... unless stopped.
            // Is_recurring will be either stored as 0 or 1 in mysql. Cannot be null. 
            /**
             * @psalm-suppress PossiblyNullArgument
             */
            $new_item->setIs_recurring($item->getIs_recurring());                
            $new_item->setProduct_unit($item->getProduct_unit() ?? '');
            $new_item->setProduct_unit_id((int)$item->getProduct_unit_id());
            $new_item->setDate($item->getDate_added());
            $iiR->save($new_item);

            // Create an item amount for this item; reversing the items amounts to negative
            $basis_item_amount = $iiaR->repoInvItemAmountquery((string)$item->getId());
            if ($basis_item_amount) {
                $new_item_amount = new InvItemAmount();
                $new_item_amount->setInv_item_id((int)$new_item->getId());
                $new_item_amount->setSubtotal(($basis_item_amount->getSubtotal()?:0.00)*-1);
                $new_item_amount->setTax_total(($basis_item_amount->getTax_total()?:0.00)*-1);
                $new_item_amount->setDiscount(($basis_item_amount->getDiscount()?:0.00)*-1);
                $new_item_amount->setTotal(($basis_item_amount->getTotal()?:0.00)*-1);
                $iiaR->save($new_item_amount);
            }
        }
    }    
}