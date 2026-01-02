<?php

declare(strict_types=1);

namespace App\Invoice\SalesOrderItem;

use App\Invoice\Entity\SalesOrderItemAmount;
use App\Invoice\Entity\SalesOrderItem;
use App\Invoice\Product\ProductRepository as PR;
use App\Invoice\SalesOrderItemAmount\SalesOrderItemAmountRepository as SoIAR;
use App\Invoice\SalesOrderItemAmount\SalesOrderItemAmountService as SoIAS;
use App\Invoice\Task\TaskRepository as taskR;
use App\Invoice\TaxRate\TaxRateRepository as TRR;
use App\Invoice\Unit\UnitRepository as UR;
use Yiisoft\Translator\TranslatorInterface as Translator;

final readonly class SalesOrderItemService
{
    public function __construct(private SalesOrderItemRepository $repository)
    {
    }

    /**
     * Used in quote/quote_to_so_quote_items subfunction in quote/quote_to_so_confirm
     * Functional: 25/11/2025: Emulates the QuoteItemService function addQuoteItemProductTask
     * @param SalesOrderItem $model
     * @param array $array
     * @param string $sales_order_id
     * @param PR $pr
     * @param taskR $taskR
     * @param SoIAR $soiar
     * @param SoIAS $soias
     * @param UR $uR
     * @param TRR $trr
     * @param Translator $translator
     */
    public function addSoItemProductTask(SalesOrderItem $model, array $array, string $sales_order_id, PR $pr, TaskR $taskR, SoIAR $soiar, SoIAS $soias, UR $uR, TRR $trr, Translator $translator): void
    {
        // This function is used in product/save_product_lookup_item_PO when adding a po using the modal
        $tax_rate_id = ((isset($array['tax_rate_id'])) ? (int) $array['tax_rate_id'] : '');
        $model->setTax_rate_id((int) $tax_rate_id);        
        $product_id = (int) ($array['product_id'] ?? null);        
        $task_id = (int) ($array['task_id'] ?? null);        
        $model->setSales_order_id((int) $sales_order_id);
        $product = $pr->repoProductquery((string) $array['product_id']);
        $name = '';
        if ($product) {            
            $model->setProduct_id($product_id);
            if (isset($array['product_id']) && $pr->repoCount((string) $product_id) > 0) {
                $name = $product->getProduct_name();
            }
            null !== $name ? $model->setName($name) : $model->setName('');
            // If the user has changed the description on the form => override default product description
            $description = ((isset($array['description']))
                                   ? (string) $array['description']
                                   : $product->getProduct_description());
            null !== $description ? 
                $model->setDescription($description) : 
                $model->setDescription($translator->translate('not.available')) ;
        }
        $task = $taskR->repoTaskquery((string) $array['task_id']);
        if ($task) {
            $model->setTask_id($task_id);
            if (isset($array['task_id']) && $taskR->repoCount((string) $task_id) > 0) {
                $name = $task->getName();
            }
            null !== $name ? $model->setName($name) : $model->setName('');
            // If the user has changed the description on the form => override default product description
            $description = (isset($array['description'])
                                      ? (string) $array['description']
                                      : $task->getDescription());

            strlen($description) > 0 ? $model->setDescription($description) : $model->setDescription($translator->translate('not.available'));
        }
        isset($array['quantity']) ? $model->setQuantity((float) $array['quantity']) : $model->setQuantity(0);
        isset($array['price']) ? $model->setPrice((float) $array['price']) : $model->setPrice(0.00);
        isset($array['discount_amount']) ? $model->setDiscount_amount((float) $array['discount_amount']) : $model->setDiscount_amount(0.00);
        isset($array['charge_amount']) ? $model->setCharge_amount((float) $array['charge_amount']) : $model->setCharge_amount(0.00);
        isset($array['order']) ? $model->setOrder((int) $array['order']) : $model->setOrder(0) ;
        // Product_unit is a string which we get from unit's name field using the unit_id
        $unit = $uR->repoUnitquery((string) $array['product_unit_id']);
        if ($unit) {
            $model->setProduct_unit($unit->getUnit_name());
        }
        $model->setProduct_unit_id((int) $array['product_unit_id']);
        // Users are required to enter a tax rate even if it is zero percent.
        $tax_rate_percentage = $this->taxrate_percentage((int) $tax_rate_id, $trr);
        $this->repository->save($model);
        if (isset($array['quantity'], $array['price'], $array['discount_amount']) && null !== $tax_rate_percentage) {
            $this->saveSalesOrderItemAmount((int) $model->getId(), (float) $array['quantity'], (float) $array['price'], (float) $array['discount_amount'], $tax_rate_percentage, $soiar, $soias);
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
        isset($array['tax_rate_id']) ? $model->setTaxRate($model->getTaxRate()?->getTaxRateId() == $array['tax_rate_id'] ? $model->getTaxRate() : null) : '';
        $tax_rate_id = ((isset($array['tax_rate_id'])) ? (int) $array['tax_rate_id'] : '');
        $model->setTax_rate_id((int) $tax_rate_id);

        isset($array['product_id']) ? $model->setProduct($model->getProduct()?->getProduct_id() == $array['product_id'] ? $model->getProduct() : null) : null;
        $product_id = ((isset($array['product_id'])) ? (int) $array['product_id'] : null);
        $model->setProduct_id((int) $product_id);
        
        isset($array['task_id']) ? $model->setTask($model->getTask()?->getId() == $array['task_id'] ? $model->getTask() : null) : null;
        $task_id = ((isset($array['task_id'])) ? (int) $array['task_id'] : null);
        $model->setTask_id((int) $task_id);
        
        $model->setSales_order_id((int) $sales_order_id);

        $product = $pr->repoProductquery((string) $array['product_id']);
        if ($product) {
            $name = (((isset($array['product_id'])) && ($pr->repoCount($product->getProduct_id()) > 0)) ? $product->getProduct_name() : '');
            $model->setName($name ?? '');
            // If the user has changed the description on the form => override default product description
            $description = ((isset($array['description']))
                                      ? (string) $array['description']
                                      : $product->getProduct_description());
            $model->setDescription($description ?? '');
        }
        isset($array['quantity']) ? $model->setQuantity((int) $array['quantity']) : '';
        isset($array['price']) ? $model->setPrice((float) $array['price']) : '';
        isset($array['discount_amount']) ? $model->setDiscount_amount((float) $array['discount_amount']) : $model->setDiscount_amount(0.00);
        isset($array['charge_amount']) ? $model->setCharge_amount((float) $array['charge_amount']) : $model->setCharge_amount(0.00);
        isset($array['peppol_po_itemid']) ? $model->setPeppol_po_itemid((string) $array['peppol_po_itemid']) : $model->setPeppol_po_itemid('');
        isset($array['peppol_po_lineid']) ? $model->setPeppol_po_lineid((string) $array['peppol_po_lineid']) : $model->setPeppol_po_lineid('');
        isset($array['order']) ? $model->setOrder((int) $array['order']) : '';
        // Product_unit is a string which we get from unit's name field using the unit_id
        $unit = $uR->repoUnitquery((string) $array['product_unit_id']);
        if ($unit) {
            $model->setProduct_unit($unit->getUnit_name());
        }
        $model->setProduct_unit_id((int) $array['product_unit_id']);
        $this->repository->save($model);
        // pass the tax_rate_id so that we can save the quote item amount
        return (int) $tax_rate_id;
    }

    /**
     * Used in salesorderitem/edit function
     * @param SalesOrderItem $model
     * @param array $array
     * @return bool
     */
    public function savePeppol_po_itemid(SalesOrderItem $model, array $array): bool
    {
        isset($array['peppol_po_itemid']) ? $model->setPeppol_po_itemid((string) $array['peppol_po_itemid']) : '';
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
        isset($array['peppol_po_lineid']) ? $model->setPeppol_po_lineid((string) $array['peppol_po_lineid']) : '';
        return $this->repository->save($model) ? true : false;
    }

    /**
     * @param int $id
     * @param TRR $trr
     * @return float|null
     */
    public function taxrate_percentage(int $id, TRR $trr): ?float
    {
        $taxrate = $trr->repoTaxRatequery((string) $id);
        if ($taxrate) {
            return $taxrate->getTaxRatePercent();
        }
        return null;
    }

    /**
     * @param int $sales_order_item_id
     * @param float $quantity
     * @param float $price
     * @param float $discount
     * @param float|null $tax_rate_percentage
     * @param SoIAR $soiar
     * @param SoIAS $soias
     */
    public function saveSalesOrderItemAmount(int $sales_order_item_id, float $quantity, float $price, float $discount, ?float $tax_rate_percentage, SoIAR $soiar, SoIAS $soias): void
    {
        $soias_array = [];
        $soias_array['sales_order_item_id'] = $sales_order_item_id;
        $sub_total = $quantity * $price;
        if (null !== $tax_rate_percentage) {
            $tax_total = ($sub_total * ($tax_rate_percentage / 100.00));
        } else {
            $tax_total = 0.00;
        }
        $discount_total = $quantity * $discount;
        $soias_array['discount'] = $discount_total;
        $soias_array['subtotal'] = $sub_total;
        $soias_array['taxtotal'] = $tax_total;
        $soias_array['total'] = $sub_total - $discount_total + $tax_total;
        if ($soiar->repoCount((string) $sales_order_item_id) === 0) {
            $soias->saveSalesOrderItemAmountNoForm(new SalesOrderItemAmount(), $soias_array);
        } else {
            $sales_order_item_amount = $soiar->repoSalesOrderItemAmountquery((string) $sales_order_item_id);
            if ($sales_order_item_amount) {
                $soias->saveSalesOrderItemAmountNoForm($sales_order_item_amount, $soias_array);
            }
        }
    }

    /**
     * @param array|SalesOrderItem|null $model
     */
    public function deleteSalesOrderItem(array|SalesOrderItem|null $model): void
    {
        $this->repository->delete($model);
    }
}
