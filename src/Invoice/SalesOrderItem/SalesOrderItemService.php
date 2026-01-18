<?php

declare(strict_types=1);

namespace App\Invoice\SalesOrderItem;

use App\Invoice\{
Entity\SalesOrderItemAmount,
Entity\SalesOrderItem,
Product\ProductRepository as PR,
SalesOrder\SalesOrderRepository as SOR,
SalesOrderItemAllowanceCharge\SalesOrderItemAllowanceChargeRepository as ACSOIR,
SalesOrderItemAmount\SalesOrderItemAmountRepository as SoIAR,
SalesOrderItemAmount\SalesOrderItemAmountService as SoIAS,
Task\TaskRepository as taskR,
TaxRate\TaxRateRepository as TRR,
Unit\UnitRepository as UR,
};
use Yiisoft\Translator\TranslatorInterface as Translator;

final readonly class SalesOrderItemService
{
    public function __construct(
        private ACSOIR $acsoiR,
        private SalesOrderItemRepository $repository,
        private SOR $soR,
        private TRR $trR,
        private PR $pR,
        private taskR $taskR,
    ) {
    }

    private function persist(
        SalesOrderItem $model,
        array $array
    ): void {
        if (isset($array['sales_order_id'])) {
            $sales_order = $this->soR
                ->repoSalesOrderUnLoadedquery(
                    (string) $array['sales_order_id']
                );
            if ($sales_order) {
                $model->setSales_order($sales_order);
                $model->setSales_order_id(
                    (int) $sales_order->getId()
                );
            }
        }
        if (isset($array['tax_rate_id'])) {
            $tax_rate = $this->trR->repoTaxRatequery(
                (string) $array['tax_rate_id']
            );
            if ($tax_rate) {
                $model->setTaxRate($tax_rate);
                $model->setTax_rate_id(
                    (int) $tax_rate->getTaxRateId()
                );
            }
        }
        if (isset($array['product_id'])) {
            $product = $this->pR->repoProductquery(
                (string) $array['product_id']
            );
            if ($product) {
                $model->setProduct($product);
                $model->setProduct_id(
                    (int) $product->getProduct_id()
                );
            }
        }
        if (isset($array['task_id'])) {
            $task = $this->taskR->repoTaskquery(
                (string) $array['task_id']
            );
            if ($task) {
                $model->setTask($task);
                $model->setTask_id((int) $task->getId());
            }
        }
    }

    /**
     * Used in quote/quote_to_so_quote_items subfunction in
     * quote/quote_to_so_confirm
     * Functional: 25/11/2025: Emulates the QuoteItemService
     * function addQuoteItemProductTask
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
    public function addSoItemProductTask(
        SalesOrderItem $model,
        array $array,
        string $sales_order_id,
        PR $pr,
        TaskR $taskR,
        SoIAR $soiar,
        SoIAS $soias,
        UR $uR,
        TRR $trr,
        Translator $translator
    ): SalesOrderItem {
        // This function is used in product/save_product_lookup_item_PO
        // when adding a po using the modal
        $array['sales_order_id'] = $sales_order_id;
        $this->persist($model, $array);
        $product_id = (int) ($array['product_id'] ?? null);        
        $task_id = (int) ($array['task_id'] ?? null);
        if (isset($array['product_id'])) {
            $product = $pr->repoProductquery(
                (string) $array['product_id']
            );
            $name = '';
            if ($product) {
            if (isset($array['product_id'])
                && $pr->repoCount((string) $product_id) > 0
            ) {
                $name = $product->getProduct_name();
            }
            null !== $name
                ? $model->setName($name)
                : $model->setName('');
            // If the user has changed the description on the form
            // => override default product description
            $description = ((isset($array['description']))
                ? (string) $array['description']
                : $product->getProduct_description());
            null !== $description
                ? $model->setDescription($description)
                : $model->setDescription(
                    $translator->translate('not.available')
                );
        }
        }
        if (isset($array['task_id'])) {
            $task = $taskR->repoTaskquery((string) $array['task_id']);
            if ($task) {
                $name = '';
                if (isset($array['task_id'])
                    && $taskR->repoCount((string) $task_id) > 0
                ) {
                    $name = $task->getName();
                }
                null !== $name
                    ? $model->setName($name)
                    : $model->setName('');
            // If the user has changed the description on the form
            // => override default product description
            $description = (isset($array['description'])
                ? (string) $array['description']
                : $task->getDescription());

            strlen($description) > 0
                ? $model->setDescription($description)
                : $model->setDescription(
                    $translator->translate('not.available')
                );
            }
        }
        isset($array['quantity'])
            ? $model->setQuantity((float) $array['quantity'])
            : $model->setQuantity(0);
        isset($array['price'])
            ? $model->setPrice((float) $array['price'])
            : $model->setPrice(0.00);
        isset($array['discount_amount'])
            ? $model->setDiscount_amount(
                (float) $array['discount_amount']
            )
            : $model->setDiscount_amount(0.00);
        isset($array['order'])
            ? $model->setOrder((int) $array['order'])
            : $model->setOrder(0);
        // Product_unit is a string which we get from unit's name
        // field using the unit_id
        $unit = $uR->repoUnitquery(
            (string) $array['product_unit_id']
        );
        if ($unit) {
            $model->setProduct_unit($unit->getUnit_name());
        }
        $model->setProduct_unit_id((int) $array['product_unit_id']);
        $this->repository->save($model);
        return $model;
    }

    /**
     * @param SalesOrderItem $model
     * @param array $array
     * @param string $sales_order_id
     * @param PR $pr
     * @param UR $uR
     * @return int
     */
    public function saveSalesOrderItem(
        SalesOrderItem $model,
        array $array,
        string $sales_order_id,
        PR $pr,
        UR $uR
    ): int {
        // This function is used in quoteitem/edit when editing
        // an item on the quote view
        // see https://github.com/cycle/orm/issues/348
        $array['sales_order_id'] = $sales_order_id;
        $this->persist($model, $array);

        $product_id = ((isset($array['product_id']))
            ? (int) $array['product_id']
            : null);
        
        $task_id = ((isset($array['task_id']))
            ? (int) $array['task_id']
            : null);

        if (isset($array['product_id'])) {
            $product = $pr->repoProductquery(
                (string) $array['product_id']
            );
            if ($product) {
                $name = (((isset($array['product_id']))
                    && ($pr->repoCount($product->getProduct_id()) > 0))
                    ? $product->getProduct_name()
                    : '');
                $model->setName($name ?? '');
                // If the user has changed the description on the form
                // => override default product description
                $description = ((isset($array['description']))
                    ? (string) $array['description']
                    : $product->getProduct_description());
                $model->setDescription($description ?? '');
            }
        }
        isset($array['quantity'])
            ? $model->setQuantity((int) $array['quantity'])
            : '';
        isset($array['price'])
            ? $model->setPrice((float) $array['price'])
            : '';
        isset($array['discount_amount'])
            ? $model->setDiscount_amount(
                (float) $array['discount_amount']
            )
            : $model->setDiscount_amount(0.00);
        isset($array['peppol_po_itemid'])
            ? $model->setPeppol_po_itemid(
                (string) $array['peppol_po_itemid']
            )
            : $model->setPeppol_po_itemid('');
        isset($array['peppol_po_lineid'])
            ? $model->setPeppol_po_lineid(
                (string) $array['peppol_po_lineid']
            )
            : $model->setPeppol_po_lineid('');
        isset($array['order'])
            ? $model->setOrder((int) $array['order'])
            : '';
        // Product_unit is a string which we get from unit's name
        // field using the unit_id
        $unit = $uR->repoUnitquery(
            (string) $array['product_unit_id']
        );
        if ($unit) {
            $model->setProduct_unit($unit->getUnit_name());
        }
        $model->setProduct_unit_id((int) $array['product_unit_id']);
        $this->repository->save($model);
        // pass the tax_rate_id so that we can save the
        // sales order item amount
        $tax_rate_id = ((isset($array['tax_rate_id']))
            ? (int) $array['tax_rate_id']
            : '');
        return (int) $tax_rate_id;
    }

    /**
     * Used in salesorderitem/edit function
     * @param SalesOrderItem $model
     * @param array $array
     * @return bool
     */
    public function savePeppol_po_itemid(
        SalesOrderItem $model,
        array $array
    ): bool {
        isset($array['peppol_po_itemid'])
            ? $model->setPeppol_po_itemid(
                (string) $array['peppol_po_itemid']
            )
            : '';
        return $this->repository->save($model) ? true : false;
    }

    /**
     * Used in salesorderitem/edit function
     * @param SalesOrderItem $model
     * @param array $array
     * @return bool
     */
    public function savePeppol_po_lineid(
        SalesOrderItem $model,
        array $array
    ): bool {
        isset($array['peppol_po_lineid'])
            ? $model->setPeppol_po_lineid(
                (string) $array['peppol_po_lineid']
            )
            : '';
        return $this->repository->save($model) ? true : false;
    }

    /**
     * @param int $id
     * @param TRR $trr
     * @return float|null
     */
    public function taxrate_percentage(
        int $id,
        TRR $trr
    ): ?float {
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
    public function saveSalesOrderItemAmount(
        int $sales_order_item_id,
        float $quantity,
        float $price,
        float $discount,
        ?float $tax_rate_percentage,
        SoIAR $soiar,
        SoIAS $soias
    ): void {
        $soias_array = [];
        $soias_array['sales_order_item_id'] = $sales_order_item_id;
        $sub_total = $quantity * $price;
        $discount_total = $quantity * $discount;
        // Fetch all allowance/charges for this item
        $all_charges = 0.00;
        $all_charges_vat_or_tax = 0.00;
        $all_allowances = 0.00;
        $all_allowances_vat_or_tax = 0.00;
        $acsois = $this->acsoiR->repoSalesOrderItemquery(
                                                (string)$sales_order_item_id);
        /** @var \App\Invoice\Entity\SalesOrderItemAllowanceCharge $acsoi */
        foreach ($acsois as $acsoi) {
            if ($acsoi->getAllowanceCharge()?->getIdentifier() == '1') {
                $all_charges += (float) $acsoi->getAmount();
                $all_charges_vat_or_tax += (float) $acsoi->getVatOrTax();
            } else {
                $all_allowances += (float) $acsoi->getAmount();
                $all_allowances_vat_or_tax += (float) $acsoi->getVatOrTax();
            }
        }
        $sopInvAc = $sub_total + $all_charges - $all_allowances;
        if (null !== $tax_rate_percentage) {
            $current_tax_total =
                ($sopInvAc - $discount_total) * ($tax_rate_percentage / 100.00);
        } else {
            $current_tax_total = 0.00;
        }
        $all_vat_or_tax = $all_charges_vat_or_tax - $all_allowances_vat_or_tax;
        $new_tax_total = $current_tax_total + $all_vat_or_tax;
        $soias_array['charge'] = $all_charges;
        $soias_array['allowance'] = $all_allowances;
        $soias_array['discount'] = $discount_total;
        $soias_array['subtotal'] = $sopInvAc;
        $soias_array['taxtotal'] = $new_tax_total;
        $soias_array['total'] = $sopInvAc - $discount_total + $new_tax_total;
        if ($soiar->repoCount((string) $sales_order_item_id) === 0) {
            $soias->saveSalesOrderItemAmountNoForm(
                new SalesOrderItemAmount(),
                $soias_array
            );
        } else {
            $sales_order_item_amount =
                $soiar->repoSalesOrderItemAmountquery(
                    (string) $sales_order_item_id
                );
            if ($sales_order_item_amount) {
                $soias->saveSalesOrderItemAmountNoForm(
                    $sales_order_item_amount,
                    $soias_array
                );
            }
        }
    }

    /**
     * @param array|SalesOrderItem|null $model
     */
    public function deleteSalesOrderItem(
        array|SalesOrderItem|null $model
    ): void {
        $this->repository->delete($model);
    }
}
