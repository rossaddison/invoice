<?php

declare(strict_types=1);

namespace App\Invoice\SalesOrderAmount;

use App\Invoice\Entity\SalesOrderAmount as SoAmount;
use App\Invoice\Entity\SalesOrderItem as SoItem;
use App\Invoice\Helpers\NumberHelper;
use App\Invoice\QuoteAmount\QuoteAmountRepository as QAR;
use App\Invoice\SalesOrder\SalesOrderRepository as SOR;
use App\Invoice\SalesOrderAmount\SalesOrderAmountRepository as SOAR;
use App\Invoice\SalesOrderItemAmount\SalesOrderItemAmountRepository as SOIAR;
use App\Invoice\SalesOrderTaxRate\SalesOrderTaxRateRepository as SOTRR;

final readonly class SalesOrderAmountService
{
    public function __construct(
        private SOAR $repository,
        private SOR $soR,
    ) {
    }

    private function persist(
        SoAmount $model,
        array $array
    ): void {
        $sales_order = $this->soR->repoSalesOrderUnLoadedquery(
            (string) $array['sales_order_id']
        );
        if ($sales_order) {
            $model->setSales_order($sales_order);
            $model->setSales_order_id((int) $sales_order->getId());
        }
    }

    /**
     * @param SoAmount $model
     * @param int $sales_order_id
     */
    public function initializeSalesOrderAmount(
        SoAmount $model,
        int $sales_order_id
    ): void {
        $model->setSales_order_id($sales_order_id);
        $model->setItem_subtotal(0.00);
        $model->setItem_tax_total(0.00);
        $model->setPackhandleship_total(0.00);
        $model->setPackhandleship_tax(0.00);
        $model->setTax_total(0.00);
        $model->setTotal(0.00);
        $this->repository->save($model);
    }

    /**
     * @param SoAmount $model
     * @param array $array
     */
    public function saveSalesOrderAmountViaCalculations(
        SoAmount $model,
        array $array
    ): void {
        /**
         * @var int $array['sales_order_id']
         * @var float $array['item_subtotal']
         * @var float $array['item_taxtotal']
         * @var float $array['tax_total']
         * @var float $array['total']
         */
        $this->persist($model, $array);
        $model->setItem_subtotal($array['item_subtotal']);
        $model->setItem_tax_total($array['item_taxtotal']);
        $model->setPackhandleship_total(
            (float) $array['packhandleship_total']
        );
        $model->setPackhandleship_tax(
            (float) $array['packhandleship_tax']
        );
        $model->setTax_total($array['tax_total']);
        $model->setTotal($array['total']);
        $this->repository->save($model);
    }
    
    /**
     * @param SoAmount $model
     * @param SalesOrderAmountForm $form
     */
    public function saveSalesOrderAmount(
        SoAmount $model,
        SalesOrderAmountForm $form
    ): void {
        null !== $form->getSales_order_id() ?
            $model->setSales_order_id($form->getSales_order_id()) : '';
        $model->setItem_subtotal(
            $form->getItem_subtotal() ?? 0.00
        );
        $model->setItem_tax_total(
            $form->getItem_tax_total() ?? 0.00
        );
        $model->setPackhandleship_total(
            (float) $form->getPackhandleship_total()
        );
        $model->setPackhandleship_tax(
            (float) $form->getPackhandleship_tax()
        );
        $model->setTax_total($form->getTax_total() ?? 0.00);
        $model->setTotal($form->getTotal() ?? 0.00);
        $this->repository->save($model);
    }
    
    /**
     * Update the SalesOrder Amounts when a salesorder item
     * allowance or charge is added to a salesorder item. Also
     * update the SalesOrder totals using Numberhelper
     * calculate quote_taxes function
     * Related logic: see SalesOrderItemAllowanceChargeController
     * functions add & edit
     * @param int $sales_order_id
     * @param SOAR $soaR
     * @param SOIAR $soiaR
     * @param SOTRR $sotrR
     * @param NumberHelper $numberHelper
     */
    public function updateSalesOrderAmount(
        int $sales_order_id,
        SOAR $soaR,
        SOIAR $soiaR,
        SOTRR $sotrR,
        NumberHelper $numberHelper
    ): void {
        $model = $this->repository->repoSalesOrderquery(
            (string) $sales_order_id
        );
        if (null !== $model) {
            $salesorder = $model->getSales_order();
            if (null !== $salesorder) {
                /**
                 * Related logic: see Entity\SalesOrder
                 *  #[HasMany(target: SoItem::class)]
                 *   private ArrayCollection $items;
                 */
                $items = $salesorder->getItems();
                $subtotal = 0.00;
                $packHandleShipTotal = 0.00;
                $packHandleShipTax = 0.00;
                $taxTotal = 0.00;
                $discount = 0.00;
                $charge = 0.00;
                $allowance = 0.00;
                /**
                 * @var SoItem $item
                 */
                foreach ($items as $item) {
                    $salesorderItemId = $item->getId();
                    $salesorderItemAmount =
                        $soiaR->repoSalesOrderItemAmountquery(
                            $salesorderItemId
                        );
                    if ($salesorderItemAmount) {
                        $subtotal +=
                            $salesorderItemAmount->getSubtotal()
                            ?? 0.00;
                        $taxTotal +=
                            $salesorderItemAmount->getTax_total()
                            ?? 0.00;
                        $discount +=
                            $salesorderItemAmount->getDiscount()
                            ?? 0.00;
                        $charge +=
                            $salesorderItemAmount->getCharge()
                            ?? 0.00;
                        $allowance +=
                            $salesorderItemAmount->getAllowance()
                            ?? 0.00;
                    }
                }

                $model->setItem_subtotal($subtotal);
                $model->setItem_tax_total($taxTotal);
                $model->setPackhandleship_total(
                    $packHandleShipTotal
                );
                $model->setPackhandleship_tax($packHandleShipTax);
                $additionalTaxTotal =
                    $numberHelper->calculate_salesorder_taxes(
                        (string) $sales_order_id,
                        $sotrR,
                        $soaR
                    );
                $model->setTax_total($additionalTaxTotal);
                $model->setTotal(
                    $subtotal + $taxTotal + $additionalTaxTotal
                );
                $this->repository->save($model);
            }
        }
    }

    /**
     * @param SoAmount|null $model
     */
    public function deleteSalesOrderAmount(
        ?SoAmount $model
    ): void {
        $this->repository->delete($model);
    }
}
