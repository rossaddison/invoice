<?php

declare(strict_types=1);

namespace App\Invoice\SalesOrderItemAllowanceCharge;

use App\Invoice\Entity\SalesOrderItemAllowanceCharge;
use App\Invoice\SalesOrderAmount\SalesOrderAmountRepository as SOAR;
use 
App\Invoice\SalesOrderItemAllowanceCharge\SalesOrderItemAllowanceChargeRepository
    as ACSOIR;
use App\Invoice\SalesOrderItemAmount\SalesOrderItemAmountRepository as SOIAR;
use App\Invoice\SalesOrderTaxRate\SalesOrderTaxRateRepository as SOTRR;
use App\Invoice\Setting\SettingRepository as SR;

final readonly class SalesOrderItemAllowanceChargeService
{
    public function __construct(private ACSOIR $repository)
    {
    }

    /**
     * @param SalesOrderItemAllowanceCharge $model
     * @param array $array
     * @param float $vat_or_tax
     */
    public function saveSalesOrderItemAllowanceCharge(
            SalesOrderItemAllowanceCharge $model,
            array $array,
            float $vat_or_tax): void
    {
        $model->nullifyRelationOnChange((int) $array['allowance_charge_id'],
                (int) $array['sales_order_item_id'],
                (int) $array['sales_order_id']);
        isset($array['sales_order_id']) ?
            $model->setSales_order_id((int) $array['sales_order_id']) : '';
        isset($array['sales_order_item_id']) ?
            $model->setSales_order_item_id((int) $array['sales_order_item_id']) : '';
        isset($array['allowance_charge_id']) ?
            $model->setAllowance_charge_id((int) $array['allowance_charge_id'])
                : '';
        isset($array['amount']) ? $model->setAmount((int) $array['amount']) : '';
        $model->setVatOrTax($vat_or_tax);
        $this->repository->save($model);
    }

    public function deleteSalesOrderItemAllowanceCharge(
        SalesOrderItemAllowanceCharge $model,
            SOAR $soaR,
            SOIAR $soiaR,
            SOTRR $sotrR, ACSOIR $acsoiR, SR $sR): void
    {
        $salesorder_item_id = $model->getSales_order_item_id();
        $this->repository->delete($model);
        $salesorder_item_amount =
            $soiaR->repoSalesOrderItemAmountquery($salesorder_item_id);
        if (null !== $salesorder_item_amount) {
            $all_charges = 0.00;
            $all_charges_vat_or_tax = 0.00;
            $all_allowances = 0.00;
            $all_allowances_vat_or_tax = 0.00;
            $acsois = $acsoiR->repoSalesOrderItemquery($salesorder_item_id);
            /** @var SalesOrderItemAllowanceCharge $acsoi */
            foreach ($acsois as $acsoi) {
                // charge add
                if ($acsoi->getAllowanceCharge()?->getIdentifier() == '1') {
                    $all_charges += (float) $acsoi->getAmount();
                    $all_charges_vat_or_tax += (float) $acsoi->getVatOrTax();
                } else {
                    // allowance subtract
                    $all_allowances += (float) $acsoi->getAmount();
                    $all_allowances_vat_or_tax += (float) $acsoi->getVatOrTax();
                }
            }
            $salesorder_item_amount->setCharge($all_charges);
            $salesorder_item_amount->setAllowance($all_allowances);
            $all_vat_or_tax =
                $all_charges_vat_or_tax - $all_allowances_vat_or_tax;
            $current_item_quantity =
                $salesorder_item_amount->getSalesOrderItem()?->getQuantity()
                ?? 0.00;
            $current_item_price =
                $salesorder_item_amount->getSalesOrderItem()?->getPrice()
                ?? 0.00;
            $discount_per_item =
              $salesorder_item_amount->getSalesOrderItem()?->getDiscount_amount()
                ?? 0.00;
            $quantity_price = $current_item_quantity * $current_item_price;
            $current_discount_item_total = $current_item_quantity *
                $discount_per_item;
            $tax_percent =
$salesorder_item_amount->getSalesOrderItem()?->getTaxRate()?->getTaxRatePercent();
            $qpIncAc = $quantity_price + $all_charges - $all_allowances;
            $current_tax_total = ($qpIncAc - $current_discount_item_total)
                * ($tax_percent ?? 0.00) / 100.00;
            $new_tax_total = $current_tax_total + $all_vat_or_tax;
            // include all item allowance charges in the subtotal
            $salesorder_item_amount->setSubtotal($qpIncAc);
            $salesorder_item_amount->setDiscount($current_discount_item_total);
            $salesorder_item_amount->setTax_total($new_tax_total);
            $overall_total = $qpIncAc - $current_discount_item_total
                + $new_tax_total;
            $salesorder_item_amount->setTotal($overall_total);
            $soiaR->save($salesorder_item_amount);
        }
    }
}
