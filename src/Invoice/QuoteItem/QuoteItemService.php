<?php

declare(strict_types=1); 

namespace App\Invoice\QuoteItem;

use App\Invoice\Entity\QuoteItemAmount;
use App\Invoice\Entity\QuoteItem;
use App\Invoice\Product\ProductRepository as PR;
use App\Invoice\QuoteItemAmount\QuoteItemAmountRepository as QIAR;
use App\Invoice\QuoteItemAmount\QuoteItemAmountService as QIAS;
use App\Invoice\TaxRate\TaxRateRepository as TRR;
use App\Invoice\Unit\UnitRepository as UR;

final class QuoteItemService
{
    private QuoteItemRepository $repository;    

    public function __construct(QuoteItemRepository $repository)
    {
        $this->repository = $repository;
    }
    
    /**
     * 
     * @param QuoteItem $model
     * @param QuoteItemForm $form
     * @param string $quote_id
     * @param PR $pr
     * @param QIAR $qiar
     * @param QIAS $qias
     * @param UR $uR
     * @param TRR $trr
     * @return void
     */
    public function addQuoteItem(QuoteItem $model, QuoteItemForm $form, string $quote_id, PR $pr, QIAR $qiar, QIAS $qias, UR $uR, TRR $trr): void
    {  
       // This function is used in product/save_product_lookup_item_quote when adding a quote using the modal 
       $tax_rate_id = ((null !==($form->getTax_rate_id())) ? $form->getTax_rate_id() : '');
       $model->setTax_rate_id((int)$tax_rate_id);
       $product_id = ((null !==($form->getProduct_id())) ? $form->getProduct_id() : '');
       $model->setProduct_id((int)$product_id);
       $model->setQuote_id((int)$quote_id);
       $product = $pr->repoProductquery($form->getProduct_id());
       $name = '';
       if ($product) {
            if (null !==$form->getProduct_id() && $pr->repoCount($product_id)> 0) {
               $name = $product->getProduct_name();            
            }
            null!==$name ? $model->setName($name) : $model->setName('');
            // If the user has changed the description on the form => override default product description
            $description = ((null !==($form->getDescription())) ? 
                                      $form->getDescription() : 
                                      $product->getProduct_description());
                 
            null!==$description ? $model->setDescription($description) : $model->setDescription('') ;
       }
       null!==$form->getQuantity() ? $model->setQuantity($form->getQuantity()) : $model->setQuantity(0);
       null!==$form->getPrice() ? $model->setPrice($form->getPrice()) : $model->setPrice(0.00);
       null!==$form->getDiscount_amount() ? $model->setDiscount_amount($form->getDiscount_amount()) : $model->setDiscount_amount(0.00);
       null!==$form->getOrder() ? $model->setOrder($form->getOrder()) : $model->setOrder(0) ;
       // Product_unit is a string which we get from unit's name field using the unit_id
       $unit = $uR->repoUnitquery((string)$form->getProduct_unit_id());
       if ($unit) {
          $model->setProduct_unit($unit->getUnit_name());
       }     
       $model->setProduct_unit_id((int)$form->getProduct_unit_id());
       // Users are required to enter a tax rate even if it is zero percent.
       $tax_rate_percentage = $this->taxrate_percentage((int)$tax_rate_id, $trr);
       if ($product_id) {
          $this->repository->save($model);  
          if (null!==$form->getQuantity() && null!==$form->getPrice() && null!==$form->getDiscount_amount() && null!==$tax_rate_percentage) {
            $this->saveQuoteItemAmount((int)$model->getId(), $form->getQuantity(), $form->getPrice(), $form->getDiscount_amount(), $tax_rate_percentage, $qiar, $qias);
          }
       }
    }  
    
    /**
     * 
     * @param QuoteItem $model
     * @param QuoteItemForm $form
     * @param string $quote_id
     * @param PR $pr
     * @param UR $uR
     * @return int
     */
    public function saveQuoteItem(QuoteItem $model, QuoteItemForm $form, string $quote_id, PR $pr, UR $uR): int
    {        
       // This function is used in quoteitem/edit when editing an item on the quote view
       // see https://github.com/cycle/orm/issues/348
       null!==$form->getTax_rate_id() ? $model->setTaxRate($model->getTaxRate()?->getTax_rate_id() == $form->getTax_rate_id() ? $model->getTaxRate() : null): '';
       $tax_rate_id = ((null !==($form->getTax_rate_id())) ? $form->getTax_rate_id() : '');
       $model->setTax_rate_id((int)$tax_rate_id);
       null!==$form->getProduct_id() ? $model->setProduct($model->getProduct()?->getProduct_id() == $form->getProduct_id() ? $model->getProduct() : null): '';
       $product_id = ((null !==($form->getProduct_id())) ? $form->getProduct_id() : '');
       $model->setProduct_id((int)$product_id);
       $model->setQuote($model->getQuote()?->getId() == $quote_id ? $model->getQuote() : null); 
       $model->setQuote_id((int)$quote_id);
       $product = $pr->repoProductquery($form->getProduct_id());
       if ($product) {
            $name = (( (null !==($form->getProduct_id())) && ($pr->repoCount($product_id)> 0) ) ? $product->getProduct_name() : '');  
            $model->setName($name ?? '');
            // If the user has changed the description on the form => override default product description
            $description = ((null !==($form->getDescription())) ? 
                                      $form->getDescription() : 
                                      $product->getProduct_description());
            $model->setDescription($description ?? '');
       }
       $model->setQuantity($form->getQuantity() ?? 0.00);
       $model->setPrice($form->getPrice() ?? 0.00);
       null!==$form->getDiscount_amount() ? $model->setDiscount_amount($form->getDiscount_amount()) : $model->setDiscount_amount(0.00);
       $model->setOrder($form->getOrder() ?? 0);
       // Product_unit is a string which we get from unit's name field using the unit_id
       $unit = $uR->repoUnitquery((string)$form->getProduct_unit_id());
       if ($unit) {
           $model->setProduct_unit($unit->getUnit_name());
       }
       $model->setProduct_unit_id((int)$form->getProduct_unit_id());
       if ($product_id) {
           $this->repository->save($model);
       } 
       // pass the tax_rate_id so that we can save the quote item amount
       return (int)$tax_rate_id;
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
     * @param int $quote_item_id
     * @param float $quantity
     * @param float $price
     * @param float $discount
     * @param float|null $tax_rate_percentage
     * @param QIAR $qiar
     * @param QIAS $qias
     * @return void
     */
    public function saveQuoteItemAmount(int $quote_item_id, float $quantity, float $price, float $discount, float|null $tax_rate_percentage, QIAR $qiar, QIAS $qias): void
    {
       $qias_array = [];
       $qias_array['quote_item_id'] = $quote_item_id;
       $sub_total = $quantity * $price;
       if (null!==$tax_rate_percentage) {
         $tax_total = ($sub_total * ($tax_rate_percentage/100));
       } else {
           $tax_total = 0.00;           
       } 
       $discount_total = $quantity*$discount;
       $qias_array['discount'] = $discount_total;
       $qias_array['subtotal'] = $sub_total;
       $qias_array['taxtotal'] = $tax_total;
       $qias_array['total'] = $sub_total - $discount_total + $tax_total;       
       if ($qiar->repoCount((string)$quote_item_id) === 0) {
         $qias->saveQuoteItemAmountNoForm(new QuoteItemAmount() , $qias_array);} else {
         $quote_item_amount = $qiar->repoQuoteItemAmountquery((string)$quote_item_id);    
         if ($quote_item_amount) {
            $qias->saveQuoteItemAmountNoForm($quote_item_amount , $qias_array);     
         }
       }                      
    }      
    
    /**
     * 
     * @param array|QuoteItem|null $model
     * @return void
     */
    public function deleteQuoteItem(array|QuoteItem|null $model): void 
    {
        $this->repository->delete($model);
    }
}