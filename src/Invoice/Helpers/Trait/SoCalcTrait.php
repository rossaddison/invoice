<?php

declare(strict_types=1);

namespace App\Invoice\Helpers\Trait;

use App\Infrastructure\Persistence\SalesOrderAmount\SalesOrderAmount;
use App\Infrastructure\Persistence\SalesOrderItem\SalesOrderItem;
use App\Infrastructure\Persistence\SalesOrderItemAmount\SalesOrderItemAmount;
use App\Invoice\SalesOrderAmount\SalesOrderAmountRepository as SOAR;
use App\Invoice\SalesOrderItem\SalesOrderItemRepository as SOIR;
use App\Invoice\SalesOrderItemAmount\SalesOrderItemAmountRepository as SOIAR;

trait SoCalcTrait
{
    /**
     * @param array<string, float> $totals
     */
    private function saveSoAmountTotals(
        int $soId,
        int $count,
        int $countSoAmount,
        SOAR $soaR,
        array $totals,
    ): void {
        if ($count > 0 && $countSoAmount > 0) {
            $soAmount = $soaR->repoSalesOrderquery($soId);
            if ($soAmount) {
                $this->setSoAmountFields($soAmount, $soId, $soaR, $totals);
            }
        }
        if ($count === 0 && $countSoAmount > 0) {
            $soAmount = $soaR->repoSalesOrderquery($soId);
            if ($soAmount) {
                $soAmount->setSalesOrderId($soId);
                $soAmount->setItemSubtotal(0.00);
                $soAmount->setItemTaxTotal(0.00);
                $soAmount->setTaxTotal(0.00);
                $soAmount->setTotal(0.00);
                $soaR->save($soAmount);
            }
        }
        if ($count === 0 && $countSoAmount === 0) {
            $soAmount = new SalesOrderAmount();
            $soAmount->setSalesOrderId($soId);
            $soAmount->setItemSubtotal(0.00);
            $soAmount->setItemTaxTotal(0.00);
            $soAmount->setTaxTotal(0.00);
            $soAmount->setTotal(0.00);
            $soaR->save($soAmount);
        }
    }

    /**
     * @param array<string, float> $totals
     */
    private function setSoAmountFields(
        SalesOrderAmount $soAmount,
        int $soId,
        SOAR $soaR,
        array $totals,
    ): void {
        $soAmount->setSalesOrderId($soId);
        $soAmount->setItemSubtotal($totals['item_subtotal'] ?: 0.00);
        $soAmount->setItemTaxTotal($totals['item_tax_total'] ?: 0.00);
        $soAmount->setPackhandleshipTotal($totals['packhandleship_total']);
        $soAmount->setPackhandleshipTax($totals['packhandleship_tax']);
        $soAmount->setTaxTotal($totals['tax_total'] ?: 0.00);
        $soAmount->setTotal($totals['total'] ?: 0.00);
        $soaR->save($soAmount);
    }

    /**
     * @param $salesorder_id
     *
     * @return (float|mixed)[]
     *
     * @psalm-return array{subtotal: float|mixed,
                     tax_total: float|mixed,
                     discount: float|mixed,
                     charge: float|mixed,
                     allowance: float|mixed, total: float|mixed}
     */
    private function salesorderCalculateTotalsofItemTotals(int $salesorder_id,
        SOIR $soiR, SOIAR $soiaR): array
    {
        $get_all_items_in_salesorder = $soiR->repoSalesOrderItemIdquery(
            $salesorder_id);
        $grand_sub_total = 0.00;
        $grand_taxtotal = 0.00;
        $grand_discount = 0.00;
        $grand_charge = 0.00;
        $grand_allowance = 0.00;
        $grand_total = 0.00;
        $totals = [
            'subtotal' => $grand_sub_total,
            'tax_total' => $grand_taxtotal,
            'discount' => $grand_discount,
            'charge' => $grand_charge,
            'allowance' => $grand_allowance,
            'total' => $grand_total,
        ];

        /** @var SalesOrderItem $item */
        foreach ($get_all_items_in_salesorder as $item) {
            /**
             * @psalm-suppress RawObjectIteration $item
             * @var string $key
             * @var string $value
             */
            foreach ($item as $key => $value) {
                if ($key === 'id') {
                    /**
                     * @var SalesOrderItemAmount $salesorder_item_amount
                     * @psalm-suppress RedundantCastGivenDocblockType $value
                     */
                    $salesorder_item_amount =
                        $soiaR->repoSalesOrderItemAmountquery((int) $value);
                    $grand_sub_total = $grand_sub_total +
                        ($salesorder_item_amount->getSubTotal() ?? 0.00) ;
                    $grand_taxtotal = $grand_taxtotal +
                        ($salesorder_item_amount->getTaxTotal() ?? 0.00);
                    $grand_charge = $grand_charge +
                        ($salesorder_item_amount->getCharge() ?? 0.00);
                    $grand_allowance = $grand_allowance +
                        ($salesorder_item_amount->getAllowance() ?? 0.00);
                    $grand_discount = $grand_discount +
                        ($salesorder_item_amount->getDiscount() ?? 0.00);
                    $grand_total = $grand_total +
                        ($salesorder_item_amount->getTotal() ?? 0.00);
                }
            }
            $totals = [
                'subtotal' => $grand_sub_total,
                'tax_total' => $grand_taxtotal,
                'discount' => $grand_discount,
                'charge' => $grand_charge,
                'allowance' => $grand_allowance,
                'total' => $grand_total,
            ];
        }
        return $totals;
    }
}
