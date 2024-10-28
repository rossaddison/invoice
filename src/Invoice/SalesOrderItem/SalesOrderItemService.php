<?php

declare(strict_types=1); 

namespace App\Invoice\SalesOrderItem;

use App\Invoice\Entity\SalesOrderItemAmount;
use App\Invoice\Entity\SalesOrderItem;
use App\Invoice\Product\ProductRepository as PR;
use App\Invoice\SalesOrderItemAmount\SalesOrderItemAmountRepository as SoIAR;
use App\Invoice\SalesOrderItemAmount\SalesOrderItemAmountService as SoIAS;
use App\Invoice\TaxRate\TaxRateRepository as TRR;
use App\Invoice\Unit\UnitRepository as UR;

final class SalesOrderItemService
{
    private SalesOrderItemRepository $repository;    

    public function __construct(SalesOrderItemRepository $repository)
    {
        $this->repository = $repository;
    }
    
    /**
     * Used in quote/quote_to_so_quote_items subfunction in quote/quote_to_so_confirm
     * @param SalesOrderItem $model
     * @param array $array
     * @param string $sales_order_id
     * @param PR $pr
     * @param SoIAR $soiar
     * @param SoIAS $soias
     * @param UR $uR
     * @param TRR $trr
     * @return void
     */
    public function addSoItem(SalesOrderItem $model, array $array, string $sales_order_id, PR $pr, SoIAR $soiar, SoIAS $soias, UR $uR, TRR $trr): void
    {  
       // This function is used in product/save_product_lookup_item_PO when adding a po using the modal 
       $tax_rate_id = ((isset($array['tax_rate_id'])) ? (int)$array['tax_rate_id'] : '');
       $model->setTax_rate_id((int)$tax_rate_id);
       $product_id = ((isset($array['product_id'])) ? (int)$array['product_id'] : '');
       $model->setProduct_id((int)$product_id);
       $model->setSales_order_id((int)$sales_order_id);
       $product = $pr->repoProductquery((string)$array['product_id']);
       $name = '';
       if ($product) {
            if (isset($array['product_id']) && $pr->repoCount((string)$array['product_id'])> 0) {
               $name = $product->getProduct_name();            
            }
            null!==$name ? $model->setName($name) : $model->setName('');
            // If the user has changed the description on the form => override default product description
            $description = ((isset($array['description'])) ? 
                                   (string)$array['description'] : 
                                   (string)$array['product_description']);
                 
            $model->setDescription($description ?: '');
       }
       isset($array['quantity']) ? $model->setQuantity((int)$array['quantity']) : $model->setQuantity(0);
       isset($array['price']) ? $model->setPrice((float)$array['price']) : $model->setPrice(0.00);
       isset($array['discount_amount']) ? $model->setDiscount_amount((float)$array['discount_amount']) : $model->setDiscount_amount(0.00);
       isset($array['charge_amount']) ? $model->setCharge_amount((float)$array['charge_amount']) : $model->setCharge_amount(0.00);
       isset($array['order']) ? $model->setOrder((int)$array['order']) : $model->setOrder(0) ;
       // Product_unit is a string which we get from unit's name field using the unit_id
       $unit = $uR->repoUnitquery((string)$array['product_unit_id']);
       if ($unit) {
          $model->setProduct_unit($unit->getUnit_name());
       }     
       $model->setProduct_unit_id((int)$array['product_unit_id']);
       // Users are required to enter a tax rate even if it is zero percent.
       $tax_rate_percentage = $this->taxrate_percentage((int)$tax_rate_id, $trr);
       if (isset($array['product_id'])) {
          $this->repository->save($model);  
          if (isset($array['quantity']) && isset($array['price']) && isset($array['discount_amount']) && null!==$tax_rate_percentage) {
            $this->saveSalesOrderItemAmount((int)$model->getId(), (float)$array['quantity'], (float)$array['price'], (float)$array['discount_amount'], $tax_rate_percentage, $soiar, $soias);
          }
       }
    }  
    
    /**
     * @param SalesOrderItem $model
     * @param array $array
     * @param string $sales_order_id
     * @param PR $pr
     * @param UR $uR
     * @return int
     */
    public function saveSalesOrderItem(SalesOrderItem $model, array $array, string $sales_order_id, PR $pr, UR $uR): int
    {        
       // This function is used in quoteitem/edit when editing an item on the quote view
       // see https://github.com/cycle/orm/issues/348
       isset($array['tax_rate_id']) ? $model->setTaxRate($model->getTaxRate()?->getTaxRateId() == $array['tax_rate_id'] ? $model->getTaxRate() : null): '';
       $tax_rate_id = ((isset($array['tax_rate_id'])) ? (int)$array['tax_rate_id'] : '');
       $model->setTax_rate_id((int)$tax_rate_id);
       
       isset($array['product_id']) ? $model->setProduct($model->getProduct()?->getProduct_id() == $array['product_id'] ? $model->getProduct() : null): '';
       $product_id = ((isset($array['product_id'])) ? (int)$array['product_id'] : '');
       $model->setProduct_id((int)$product_id);
       
       !empty($sales_order_id) ? $model->setSalesOrder($model->getSalesOrder()?->getId() == $sales_order_id ? $model->getSalesOrder() : null): ''; 
       // The sales order is passed as a parameter
       $model->setSales_order_id((int)$sales_order_id);
       
       $product = $pr->repoProductquery((string)$array['product_id']);
       if ($product) {
            $name = (((isset($array['product_id'])) && ($pr->repoCount($product->getProduct_id())> 0) ) ? $product->getProduct_name() : '');  
            $model->setName($name ?? '');
            // If the user has changed the description on the form => override default product description
            $description = ((isset($array['description'])) ? 
                                      (string)$array['description'] : 
                                      $product->getProduct_description());
            $model->setDescription($description ?? '');
       }
       isset($array['quantity']) ? $model->setQuantity((int)$array['quantity']) : '';
       isset($array['price']) ? $model->setPrice((float)$array['price']) : '';
       isset($array['discount_amount']) ? $model->setDiscount_amount((float)$array['discount_amount']) : $model->setDiscount_amount(0.00);
       isset($array['charget_amount']) ? $model->setCharge_amount((float)$array['discount_amount']) : $model->setCharge_amount(0.00);
       isset($array['peppol_po_itemid']) ? $model->setPeppol_po_itemid((string)$array['peppol_po_itemid']) : $model->setPeppol_po_itemid('');
       isset($array['peppol_po_lineid']) ? $model->setPeppol_po_lineid((string)$array['peppol_po_lineid']) : $model->setPeppol_po_lineid('');
       isset($array['order']) ? $model->setOrder((int)$array['order']) : '';
       // Product_unit is a string which we get from unit's name field using the unit_id
       $unit = $uR->repoUnitquery((string)$array['product_unit_id']);
       if ($unit) {
           $model->setProduct_unit($unit->getUnit_name());
       }
       $model->setProduct_unit_id((int)$array['product_unit_id']);
       $this->repository->save($model);
       // pass the tax_rate_id so that we can save the quote item amount
       return (int)$tax_rate_id;
    } 
    
    /**
     * Used in salesorderitem/edit function
     * @param SalesOrderItem $model
     * @param array $array
     * @return bool
     */
    public function savePeppol_po_itemid(SalesOrderItem $model, array $array): bool
    {        
       isset($array['peppol_po_itemid']) ? $model->setPeppol_po_itemid((string)$array['peppol_po_itemid']) : '';
       return $this->repository->save($model) ? true : false;
    }
    
    /**
     * Used in salesorderitem/edit function
     * @param SalesOrderItem $model
     * @param array $array
     * @return bool
     */
    public function savePeppol_po_lineid(SalesOrderItem $model, array $array): bool
    {        
       isset($array['peppol_po_lineid']) ? $model->setPeppol_po_lineid((string)$array['peppol_po_lineid']) : '';
       return $this->repository->save($model) ? true : false;
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
            $percentage = $taxrate->getTaxRatePercent();        
            return $percentage;
        }
        return null;
    }
    
    /**
     * 
     * @param int $so_item_id
     * @param float $quantity
     * @param float $price
     * @param float $discount
     * @param float|null $tax_rate_percentage
     * @param SoIAR $soiar
     * @param SoIAS $soias
     * @return void
     */
    public function saveSalesOrderItemAmount(int $so_item_id, float $quantity, float $price, float $discount, float|null $tax_rate_percentage, SoIAR $soiar, SoIAS $soias): void
    {
       $soias_array = [];
       $soias_array['so_item_id'] = $so_item_id;
       $sub_total = $quantity * $price;
       if (null!==$tax_rate_percentage) {
         $tax_total = ($sub_total * ($tax_rate_percentage/100));
       } else {
         $tax_total = 0.00;           
       } 
       $discount_total = $quantity*$discount;
       $soias_array['discount'] = $discount_total;
       $soias_array['subtotal'] = $sub_total;
       $soias_array['taxtotal'] = $tax_total;
       $soias_array['total'] = $sub_total - $discount_total + $tax_total;       
       if ($soiar->repoCount((string)$so_item_id) === 0) {
         $soias->saveSalesOrderItemAmountNoForm(new SalesOrderItemAmount(), $soias_array);} else {
         $so_item_amount = $soiar->repoSalesOrderItemAmountquery((string)$so_item_id);    
         if ($so_item_amount) {
            $soias->saveSalesOrderItemAmountNoForm($so_item_amount, $soias_array);     
         }
       }                      
    }      
    
    /**
     * 
     * @param array|SalesOrderItem|null $model
     * @return void
     */
    public function deleteSalesOrderItem(array|SalesOrderItem|null $model): void 
    {
        $this->repository->delete($model);
    }
}