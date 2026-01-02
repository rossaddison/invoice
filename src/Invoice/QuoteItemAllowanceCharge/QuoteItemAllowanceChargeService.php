<?php

declare(strict_types=1);

namespace App\Invoice\QuoteItemAllowanceCharge;

use App\Invoice\Entity\QuoteItemAllowanceCharge;
use App\Invoice\QuoteAmount\QuoteAmountRepository as QAR;
use App\Invoice\QuoteItemAllowanceCharge\QuoteItemAllowanceChargeRepository as ACQIR;
use App\Invoice\QuoteItemAmount\QuoteItemAmountRepository as QIAR;
use App\Invoice\QuoteTaxRate\QuoteTaxRateRepository as QTRR;
use App\Invoice\Setting\SettingRepository as SR;

final readonly class QuoteItemAllowanceChargeService
{
    public function __construct(private ACQIR $repository)
    {
    }

    /**
     * @param QuoteItemAllowanceCharge $model
     * @param array $array
     * @param float $vat_or_tax
     */
    public function saveQuoteItemAllowanceCharge(
            QuoteItemAllowanceCharge $model,
            array $array,
            float $vat_or_tax): void
    {
        $model->nullifyRelationOnChange((int) $array['allowance_charge_id'],
                (int) $array['quote_item_id'],
                (int) $array['quote_id']);
        isset($array['quote_id']) ?
            $model->setQuote_id((int) $array['quote_id']) : '';
        isset($array['quote_item_id']) ?
            $model->setQuote_item_id((int) $array['quote_item_id']) : '';
        isset($array['allowance_charge_id']) ?
            $model->setAllowance_charge_id((int) $array['allowance_charge_id'])
                : '';
        isset($array['amount']) ? $model->setAmount((int) $array['amount']) : '';
        $model->setVatOrTax($vat_or_tax);
        $this->repository->save($model);
    }

    public function deleteQuoteItemAllowanceCharge(
        QuoteItemAllowanceCharge $model,
            QAR $qaR,
            QIAR $qiaR,
            QTRR $qtrR, ACQIR $acqiR, SR $sR): void
    {
        // before deleting the allowance/charge, record its related
        //  quote_item_id so that we can update the quote_item_amount record
        $quote_item_id = $model->getQuote_item_id();
        // delete the allowance / charge
        $this->repository->delete($model);
        $quote_item_amount = $qiaR->repoQuoteItemAmountquery($quote_item_id);
        // rebuild the accumulative totals for the quote_item_amount
        if (null !== $quote_item_amount) {
            $all_charges = 0.00;
            $all_charges_vat_or_tax = 0.00;
            $all_allowances = 0.00;
            $all_allowances_vat_or_tax = 0.00;
            $acqis = $acqiR->repoQuoteItemquery($quote_item_id);
            /** @var QuoteItemAllowanceCharge $acqi */
            foreach ($acqis as $acqi) {
                // charge add
                if ($acqi->getAllowanceCharge()?->getIdentifier() == '1') {
                    $all_charges += (float) $acqi->getAmount();
                    $all_charges_vat_or_tax += (float) $acqi->getVatOrTax();
                } else {
                    // allowance subtract
                    $all_allowances += (float) $acqi->getAmount();
                    $all_allowances_vat_or_tax += (float) $acqi->getVatOrTax();
                }
            }
            // Record the rebuilt accumulative charges and allowances totals in
            // the QuoteItemAmount Entity
            $quote_item_amount->setCharge($all_charges);
            $quote_item_amount->setAllowance($all_allowances);
            $all_vat_or_tax = $all_charges_vat_or_tax - $all_allowances_vat_or_tax;
            $current_item_quantity = $quote_item_amount->getQuoteItem()?->getQuantity()
                ?? 0.00;
            $current_item_price = $quote_item_amount->getQuoteItem()?->getPrice()
                ?? 0.00;
            $discount_per_item = $quote_item_amount->getQuoteItem()?->getDiscount_amount()
                ?? 0.00;
            $quantity_price = $current_item_quantity * $current_item_price;
            $current_discount_item_total = $current_item_quantity *
                $discount_per_item;
            $tax_percent =
                $quote_item_amount->getQuoteItem()?->getTaxRate()?->getTaxRatePercent();
            $qpIncAc = $quantity_price + $all_charges - $all_allowances;
            $current_tax_total = ($qpIncAc - $current_discount_item_total)
                * ($tax_percent ?? 0.00) / 100.00;
            $new_tax_total = $current_tax_total + $all_vat_or_tax;
            // include all item allowance charges in the subtotal
            $quote_item_amount->setSubtotal($qpIncAc);
            $quote_item_amount->setDiscount($current_discount_item_total);
            $quote_item_amount->setTax_total($new_tax_total);
            $overall_total = $qpIncAc - $current_discount_item_total
                + $new_tax_total;
            $quote_item_amount->setTotal($overall_total);
            $qiaR->save($quote_item_amount);
        }
    }
}
