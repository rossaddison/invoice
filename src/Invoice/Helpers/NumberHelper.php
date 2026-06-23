<?php

declare(strict_types=1);

namespace App\Invoice\Helpers;

use App\Infrastructure\Persistence\Inv\Inv;

use App\Infrastructure\Persistence\InvAllowanceCharge\InvAllowanceCharge;
use App\Infrastructure\Persistence\QuoteAllowanceCharge\QuoteAllowanceCharge;
use App\Infrastructure\Persistence\SalesOrderAllowanceCharge\{
    SalesOrderAllowanceCharge,
};
use App\Infrastructure\Persistence\SalesOrderAmount\SalesOrderAmount;
use App\Infrastructure\Persistence\SalesOrderItem\SalesOrderItem;
use App\Infrastructure\Persistence\SalesOrderItemAmount\SalesOrderItemAmount;
use App\Infrastructure\Persistence\SalesOrderTaxRate\SalesOrderTaxRate;
use App\Infrastructure\Persistence\InvAmount\InvAmount;
use App\Infrastructure\Persistence\InvItem\InvItem;
use App\Infrastructure\Persistence\InvTaxRate\InvTaxRate;
use App\Infrastructure\Persistence\Payment\Payment;
use App\Infrastructure\Persistence\QuoteAmount\QuoteAmount;
use App\Infrastructure\Persistence\QuoteItem\QuoteItem;
use App\Infrastructure\Persistence\QuoteItemAmount\QuoteItemAmount;
use App\Infrastructure\Persistence\QuoteTaxRate\QuoteTaxRate;
use App\Invoice\Setting\SettingRepository as SRepo;
use App\Invoice\QuoteItem\QuoteItemRepository as QIR;
use App\Invoice\InvItem\InvItemRepository as IIR;
use App\Invoice\QuoteAmount\QuoteAmountRepository as QAR;

use App\Invoice\Helpers\Trait\SoCalcTrait;
use App\Invoice\InvAllowanceCharge\InvAllowanceChargeRepository as ACIR;
use App\Invoice\QuoteAllowanceCharge\QuoteAllowanceChargeRepository as ACQR;
use App\Invoice\SalesOrderAllowanceCharge\SalesOrderAllowanceChargeRepository
    as ACSOR;
use App\Invoice\SalesOrderItem\SalesOrderItemRepository as SOIR;
use App\Invoice\SalesOrderItemAmount\SalesOrderItemAmountRepository as SOIAR;
use App\Invoice\SalesOrderTaxRate\SalesOrderTaxRateRepository as SOTRR;
use App\Invoice\SalesOrderAmount\SalesOrderAmountRepository as SOAR;
use App\Invoice\SalesOrder\SalesOrderRepository as SOR;
use App\Invoice\InvAmount\InvAmountRepository as IAR;
use App\Invoice\Quote\QuoteRepository as QR;
use App\Invoice\Inv\InvRepository as IR;
use App\Invoice\QuoteItemAmount\QuoteItemAmountRepository as QIAR;
use App\Invoice\InvItemAmount\InvItemAmountRepository as IIAR;
use App\Invoice\QuoteTaxRate\QuoteTaxRateRepository as QTRR;
use App\Invoice\InvTaxRate\InvTaxRateRepository as ITRR;
use App\Invoice\Payment\PaymentRepository as PYMR;

final readonly class NumberHelper
{
    use SoCalcTrait;

    public function __construct(private SRepo $s)
    {
    }

    /**
     * @param mixed|null $amount
     */
    public function formatCurrency(mixed $amount = null): string
    {
        $this->s->loadSettings();
        $currency_symbol = $this->s->getSetting('currency_symbol');
        $currency_symbol_placement = $this->s->getSetting(
                                                'currency_symbol_placement');
        $thousands_separator = $this->s->getSetting('thousands_separator');
        $decimal_point = $this->s->getSetting('decimal_point');
        if ($currency_symbol_placement == 'before') {
            return $currency_symbol . number_format(
                (float) $amount, ($decimal_point) ? 2 : 0, $decimal_point,
                    $thousands_separator
            );
        }
        if ($currency_symbol_placement == 'afterspace') {
            return number_format(
                (float) $amount, ($decimal_point) ? 2 : 0, $decimal_point,
                    $thousands_separator
            ) . '&nbsp;' . $currency_symbol;
        }
        return number_format(
            (float) $amount, ($decimal_point) ? 2 : 0, $decimal_point,
                $thousands_separator) . $currency_symbol;
    }

    /**
     * Output the amount as a currency amount, e.g. 1.234,56
     *
     * @param mixed|null $amount
     * @return string|null
     */
    public function formatAmount(mixed $amount = null): ?string
    {
        $this->s->loadSettings();
        if (null !== $amount) {
            $thousands_separator = $this->s->getSetting('thousands_separator');
            $decimal_point = $this->s->getSetting('decimal_point');
            return number_format(
                (float) $amount, ($decimal_point) ? 2 : 0,
                    $decimal_point, $thousands_separator);
        }
        return null;
    }

    /**
     * Standardize an amount based on the system settings
     *
     * @param mixed $amount
     * @return mixed
     */
    public function standardizeAmount(mixed $amount)
    {
        $this->s->loadSettings();
        $thousands_separator = $this->s->getSetting('thousands_separator');
        $decimal_point = $this->s->getSetting('decimal_point');
        /** @var array<array-key, float|int|string>|string $amount */
        $amt = str_replace($thousands_separator, '', $amount);
        return str_replace($decimal_point, '.', $amt);
    }

    /**
     * @param $quote_id
     */
    public function calculateQuote(
        int $quote_id,
        ACQR $acqR, QIR $qiR, QIAR $qiaR, QTRR $qtrR, QAR $qaR, QR $qR): void
    {
        $quote_allowance_charge_amount_total = 0.00;
        $quote_allowance_charge_tax_total = 0.00;

        // Get all items that belong to a specific quote by accessing $qiR
        // Sum all these item's amounts
        // -------------------------
        // Quote Subtotal + Item Tax
        // -------------------------
        $quote_item_amounts = $this->quoteCalculateTotalsofItemTotals(
            $quote_id, $qiR, $qiaR);

        // individual quote_item_amount['subtotal'] already includes
        // charges and allowances
        $quote_item_subtotal_discount_inclusive =
            (float) $quote_item_amounts['subtotal']
                - (float) $quote_item_amounts['discount'];
        $quote_subtotal_discount_and_charge_and_tax_included =
                $quote_item_subtotal_discount_inclusive
                    + (float) $quote_item_amounts['tax_total'];
        //----------
        // Quote Tax
        // ---------
        if ($this->s->getSetting('enable_vat_registration') === '0') {
            $quote_tax_rate_total = $this->calculateQuoteTaxes(
                                                        $quote_id, $qtrR, $qaR);
        } else {
            // No Quote Taxes are allowed under the VAT regime.
            $quote_tax_rate_total = 0.00;
        }

        $quote_allowance_charges = $acqR->repoACQquery($quote_id);
        /** @var QuoteAllowanceCharge $quote_allowance_charge */
        foreach ($quote_allowance_charges as $quote_allowance_charge) {
            $isCharge =
                $quote_allowance_charge->getAllowanceCharge()?->getIdentifier();
            if ($isCharge) {
                $quote_allowance_charge_amount_total +=
                    (float) $quote_allowance_charge->getAmount();
                $quote_allowance_charge_tax_total +=
                    (float) $quote_allowance_charge->getVatOrTax();
            } else {
                $quote_allowance_charge_amount_total -=
                    (float) $quote_allowance_charge->getAmount();
                $quote_allowance_charge_tax_total -=
                    (float) $quote_allowance_charge->getVatOrTax();
            }
        }

        //--------------------------------------------------
        // Before Early Cash Settlement Discount and Charge
        // -------------------------------------------------
        $final_discountable_and_chargeable_total
            = $quote_subtotal_discount_and_charge_and_tax_included
            + $quote_tax_rate_total
            + $quote_allowance_charge_amount_total
            + $quote_allowance_charge_tax_total;

        //------------------------------------------------
        // Final Grand Total after Applying Cash Discount
        // -----------------------------------------------
        $quote_total =
            $this->quoteIncludeCustomerDiscountRequest(
                $quote_id, $final_discountable_and_chargeable_total, $qR);

        $count = $qiR->repoCount($quote_id);
        $count_quote_amount = $qaR->repoQuoteAmountCount($quote_id);
        $totals = [
            'item_subtotal'       => $quote_item_subtotal_discount_inclusive,
            'item_tax_total'      => (float) $quote_item_amounts['tax_total'],
            'packhandleship_total' => $quote_allowance_charge_amount_total,
            'packhandleship_tax'  => $quote_allowance_charge_tax_total,
            'tax_total'           => $quote_tax_rate_total,
            'total'               => $quote_total,
        ];
        $this->saveQuoteAmountTotals($quote_id, $count, $count_quote_amount, $qaR, $totals);
    }

    /**
     * @param array<string, float> $totals
     */
    private function saveQuoteAmountTotals(
        int $quoteId,
        int $count,
        int $countQuoteAmount,
        QAR $qaR,
        array $totals,
    ): void {
        if ($count > 0 && $countQuoteAmount > 0) {
            $quoteAmount = $qaR->repoQuotequery($quoteId);
            if ($quoteAmount) {
                $this->setQuoteAmountFields($quoteAmount, $quoteId, $qaR, $totals);
            }
        }
        if ($count === 0 && $countQuoteAmount > 0) {
            $quoteAmount = $qaR->repoQuotequery($quoteId);
            if ($quoteAmount) {
                $quoteAmount->setQuoteId($quoteId);
                $quoteAmount->setItemSubtotal(0.00);
                $quoteAmount->setItemTaxTotal(0.00);
                $quoteAmount->setTaxTotal(0.00);
                $quoteAmount->setTotal(0.00);
                $qaR->save($quoteAmount);
            }
        }
        if ($count === 0 && $countQuoteAmount === 0) {
            $quoteAmount = new QuoteAmount();
            $quoteAmount->setQuoteId($quoteId);
            $quoteAmount->setItemSubtotal(0.00);
            $quoteAmount->setItemTaxTotal(0.00);
            $quoteAmount->setTaxTotal(0.00);
            $quoteAmount->setTotal(0.00);
            $qaR->save($quoteAmount);
        }
    }

    /**
     * @param array<string, float> $totals
     */
    private function setQuoteAmountFields(
        QuoteAmount $quoteAmount,
        int $quoteId,
        QAR $qaR,
        array $totals,
    ): void {
        $quoteAmount->setQuoteId($quoteId);
        $quoteAmount->setItemSubtotal($totals['item_subtotal'] ?: 0.00);
        $quoteAmount->setItemTaxTotal($totals['item_tax_total'] ?: 0.00);
        $quoteAmount->setPackhandleshipTotal($totals['packhandleship_total']);
        $quoteAmount->setPackhandleshipTax($totals['packhandleship_tax']);
        $quoteAmount->setTaxTotal($totals['tax_total'] ?: 0.00);
        $quoteAmount->setTotal($totals['total'] ?: 0.00);
        $qaR->save($quoteAmount);
    }

    /**
     * @param $salesorder_id
     */
    public function calculateSo(
        int $salesorder_id,
        ACSOR $acsoR, SOIR $soiR, SOIAR $soiaR, SOTRR $sotrR, SOAR $soaR,
        SOR $soR): void
    {
        $salesorder_allowance_charge_amount_total = 0.00;
        $salesorder_allowance_charge_tax_total = 0.00;

        // Get all items that belong to a specific salesorder by accessing $soiR
        // Sum all these item's amounts
        // -------------------------
        // SalesOrder Subtotal + Item Tax
        // -------------------------
        $salesorder_item_amounts = $this->salesorderCalculateTotalsofItemTotals(
            $salesorder_id, $soiR, $soiaR);

        // individual salesorder_item_amount['subtotal'] already includes
        // charges and allowances
        $salesorder_item_subtotal_discount_inclusive =
            (float) $salesorder_item_amounts['subtotal']
                - (float) $salesorder_item_amounts['discount'];
        $salesorder_subtotal_discount_and_charge_and_tax_included =
                $salesorder_item_subtotal_discount_inclusive
                    + (float) $salesorder_item_amounts['tax_total'];
        //----------
        // SalesOrder Tax
        // ---------
        if ($this->s->getSetting('enable_vat_registration') === '0') {
            $salesorder_tax_rate_total = $this->calculateSalesorderTaxes(
               $salesorder_id, $sotrR, $soaR);
        } else {
            // No SalesOrder Taxes are allowed under the VAT regime.
            $salesorder_tax_rate_total = 0.00;
        }

        $salesorder_allowance_charges = $acsoR->repoACSOquery($salesorder_id);
        /** @var SalesOrderAllowanceCharge $salesorder_allowance_charge */
        foreach ($salesorder_allowance_charges as $salesorder_allowance_charge) {
            $isCharge =
            $salesorder_allowance_charge->getAllowanceCharge()?->getIdentifier();
            if ($isCharge) {
                $salesorder_allowance_charge_amount_total +=
                    (float) $salesorder_allowance_charge->getAmount();
                $salesorder_allowance_charge_tax_total +=
                    (float) $salesorder_allowance_charge->getVatOrTax();
            } else {
                $salesorder_allowance_charge_amount_total -=
                    (float) $salesorder_allowance_charge->getAmount();
                $salesorder_allowance_charge_tax_total -=
                    (float) $salesorder_allowance_charge->getVatOrTax();
            }
        }

        //--------------------------------------------------
        // Before Early Cash Settlement Discount and Charge
        // -------------------------------------------------
        $final_discountable_and_chargeable_total
            = $salesorder_subtotal_discount_and_charge_and_tax_included
            + $salesorder_tax_rate_total
            + $salesorder_allowance_charge_amount_total
            + $salesorder_allowance_charge_tax_total;

        //------------------------------------------------
        // Final Grand Total after Applying Cash Discount
        // -----------------------------------------------
        $salesorder_total =
            $this->salesorderIncludeCustomerDiscountRequest(
                $salesorder_id, $final_discountable_and_chargeable_total, $soR);

        $count = $soiR->repoCount($salesorder_id);
        $count_salesorder_amount = $soaR->repoSalesOrderAmountCount($salesorder_id);
        $totals = [
            'item_subtotal'       => $salesorder_item_subtotal_discount_inclusive,
            'item_tax_total'      => (float) $salesorder_item_amounts['tax_total'],
            'packhandleship_total' => $salesorder_allowance_charge_amount_total,
            'packhandleship_tax'  => $salesorder_allowance_charge_tax_total,
            'tax_total'           => $salesorder_tax_rate_total,
            'total'               => $salesorder_total,
        ];
        $this->saveSoAmountTotals($salesorder_id, $count, $count_salesorder_amount, $soaR, $totals);
    }

    public function calculateInv(int $inv_id, CalcInvDeps $deps): void
    {
        $aciR = $deps->aciR;
        $iiR = $deps->iiR;
        $iiaR = $deps->iiaR;
        $itrR = $deps->itrR;
        $iaR = $deps->iaR;
        $iR = $deps->iR;
        $inv_allowance_charge_amount_total = 0.00;
        $inv_allowance_charge_tax_total = 0.00;
        // Get all items that belong to a specific invoice by accessing $iiR
        // Sum all these item's amounts
        // -------------------------
        // Invoice Subtotal + Item Tax
        // -------------------------
        $inv_item_amounts = $this->invCalculateTotalsofItemTotals(
                                                          $inv_id, $iiR, $iiaR);
        $inv_item_subtotal_discount
        // individual inv_item_amount['subtotal'] already includes
        // charges and allowances
        = (float) $inv_item_amounts['subtotal']
        - (float) $inv_item_amounts['discount'];

        $inv_subtotal_discount_and_charge_and_tax_included
        = $inv_item_subtotal_discount
        + (float) $inv_item_amounts['tax_total'];

        //----------
        // Invoice Tax
        // ---------
        $inv_tax_rate_total = $this->s->getSetting(
            'enable_vat_registration') === '0' ? $this->calculateInvTaxes(
                                                   $inv_id, $itrR, $iaR) : 0.00;

        $inv_allowance_charges = $aciR->repoACIquery($inv_id);
        /** @var InvAllowanceCharge $inv_allowance_charge */
        foreach ($inv_allowance_charges as $inv_allowance_charge) {
            $isCharge =
                $inv_allowance_charge->getAllowanceCharge()?->getIdentifier();
            if ($isCharge) {
                $inv_allowance_charge_amount_total +=
                    (float) $inv_allowance_charge->getAmount();
                $inv_allowance_charge_tax_total +=
                    (float) $inv_allowance_charge->getVatOrTax();
            } else {
                $inv_allowance_charge_amount_total -=
                    (float) $inv_allowance_charge->getAmount();
                $inv_allowance_charge_tax_total -=
                    (float) $inv_allowance_charge->getVatOrTax();
            }
        }
        //-------------------------------------------------
        // Before Early Cash Settlement Discount and Charge
        // ------------------------------------------------
        $final_discountable_and_chargeable_total
                = $inv_subtotal_discount_and_charge_and_tax_included
                + $inv_tax_rate_total
                + $inv_allowance_charge_amount_total
                + $inv_allowance_charge_tax_total;
        //-----------------------------------------------
        // Note: Not applicable to VAT system: inv...view
        // ...Edit input boxes will be hidden since Early
        //  Settlement discounts already accounted for in the line items
        // Final Grand Total after Applying Cash Discount
        // ----------------------------------------------
        $inv_total = $this->invIncludeCustomerDiscountRequest(
                        $inv_id, $final_discountable_and_chargeable_total, $iR);

        //---------------------------------------------------------------------
        // Give the Invoice its summary of amounts at the bottom of the invoice
        //---------------------------------------------------------------------
        $count = $iiR->repoCount($inv_id);
        $count_inv_amount = $iaR->repoInvAmountCount($inv_id);
        //At least one item and a preexisting invoice amount record exists =>
        //Update the Invoice Amount Record
        if (($count > 0) && ($count_inv_amount > 0)) {
            $inv_amount = $iaR->repoInvquery($inv_id);
            if ($inv_amount) {
                $inv_amount->setInvId($inv_id);
                $inv_amount->setItemSubtotal(
                                           $inv_item_subtotal_discount ?: 0.00);
                $inv_amount->setItemTaxTotal(
                                (float) $inv_item_amounts['tax_total'] ?: 0.00);
                /** Overall i.e. not line item total */
                $inv_amount->setPackhandleshipTotal(
                                            $inv_allowance_charge_amount_total);
                /** Overall i.e. not line item tax e.g. vat or gst */
                $inv_amount->setPackhandleshipTax(
                                               $inv_allowance_charge_tax_total);
                $inv_amount->setTaxTotal($inv_tax_rate_total ?: 0.00);
                $inv_amount->setTotal($inv_total ?: 0.00);
                $this->calculateAndSetBalance($inv_amount, $inv_id, $inv_total, $deps);
            }
        }
        // There are no longer any items on the invoice so initialize the
        // Invoice Amount Record to zero
        if (($count === 0) && ($count_inv_amount > 0)) {
            $inv_amount = $iaR->repoInvquery($inv_id);
            if ($inv_amount) {
                $inv_amount->setInvId($inv_id);
                $inv_amount->setItemSubtotal(0.00);
                $inv_amount->setItemTaxTotal(0.00);
                $inv_amount->setTaxTotal(0.00);
                $inv_amount->setTotal(0.00);
                $iaR->save($inv_amount);
            }
        }
        if (($count === 0) && ($count_inv_amount === 0)) {
            // Create an Invoice  Amount Record for this invoice if it does not
            // exist even if there are no items
            $inv_amount = new InvAmount();
            $inv_amount->setInvId($inv_id);
            $inv_amount->setItemSubtotal(0.00);
            $inv_amount->setItemTaxTotal(0.00);
            $inv_amount->setTaxTotal(0.00);
            $inv_amount->setTotal(0.00);
            $iaR->save($inv_amount);
        }
    }

    private function calculateAndSetBalance(
        InvAmount $inv_amount,
        int $inv_id,
        float $inv_total,
        CalcInvDeps $deps,
    ): void {
        $payments = $deps->pymR->repoCount($inv_id) > 0
            ? $deps->pymR->repoInvquery($inv_id)
            : [];
        $total_paid = 0.00;
        /** @var Payment $payment */
        foreach ($payments as $payment) {
            $total_paid += (float) $payment->getAmount();
        }
        $inv_amount->setPaid($total_paid);
        $balance = $inv_total - $total_paid;
        $inv_amount->setBalance($balance);
        $deps->iaR->save($inv_amount);
        $invoice = $deps->iR->repoInvUnLoadedquery($inv_id) ?? null;
        if ($inv_total > 0.00 && $total_paid > 0.00) {
            $this->invBalanceZeroSetToReadOnlyIfFullyPaid($deps->iR, $this->s,
                $invoice, $balance);
        }
    }

    /**
     * @param $quote_id
     *
     * @return (float|mixed)[]
     *
     * @psalm-return array{subtotal: float|mixed,
                     tax_total: float|mixed,
                     discount: float|mixed,
                     charge: float|mixed,
                     allowance: float|mixed,
                     total: float|mixed}
     */
    private function quoteCalculateTotalsofItemTotals(int $quote_id,
        QIR $qiR, QIAR $qiaR): array
    {
        $get_all_items_in_quote = $qiR->repoQuoteItemIdquery($quote_id);
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

        /** @var QuoteItem $item */
        foreach ($get_all_items_in_quote as $item) {
            /**
             * @psalm-suppress RawObjectIteration $item
             * @var string $key
             * @var string $value
             */
            foreach ($item as $key => $value) {
                if ($key === 'id') {
                    /**
                     * @var QuoteItemAmount $quote_item_amount
                     * @psalm-suppress RedundantCastGivenDocblockType $value
                     */
                    $quote_item_amount = $qiaR->repoQuoteItemAmountquery(
                        (int) $value);
                    $grand_sub_total = $grand_sub_total +
                            ($quote_item_amount->getSubTotal() ?? 0.00) ;
                    $grand_taxtotal = $grand_taxtotal +
                            ($quote_item_amount->getTaxTotal() ?? 0.00);
                    $grand_charge = $grand_charge +
                            ($quote_item_amount->getCharge() ?? 0.00);
                    $grand_allowance = $grand_allowance +
                            ($quote_item_amount->getAllowance() ?? 0.00);
                    $grand_discount = $grand_discount +
                            ($quote_item_amount->getDiscount() ?? 0.00);
                    $grand_total = $grand_total +
                            ($quote_item_amount->getTotal() ?? 0.00);
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

    /**
     * @psalm-param IR<Inv> $iR
     */
    private function invBalanceZeroSetToReadOnlyIfFullyPaid(IR $iR,
                                SRepo $sR, ?Inv $invoice, float $balance): void
    {
// draft => 1, sent => 2, viewed => 3, paid => 4
// As soon as the balance on the invoice is zero and the read-only-toggle is 4
// ie. paid,
// for Administrative purposes set the invoice to read-only to avoid tampering
            if (($sR->getSetting('read_only_toggle') === (string) 4)
                    && null !== $invoice
// Force the user to set the status to read-only manually i.e. view..edit  if
// it is a deliberate zero invoice i.e. `paid` and `total` equaling zero ....
// here by only setting to read only if `paid` and `total` are greater than zero.
                    && $balance == 0.00
                    && ($invoice->getInvAmount()->getPaid() > 0.00)
                    && ($invoice->getInvAmount()->getTotal() > 0.00)) {
                $invoice->setIsReadOnly(true);
                // Set the status to paid
                $invoice->setStatusId(4);
                $iR->save($invoice);
        }
    }

    /**
     * @param int $inv_id
     * @param IIR $iiR
     * @param IIAR $iiaR
     * @return array
     */
    public function invCalculateTotalsofItemTotals(
                                    int $inv_id, IIR $iiR, IIAR $iiaR): array
    {
        $get_all_items_in_inv = $iiR->repoInvItemIdquery($inv_id);
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
        /** @var InvItem $item */
        foreach ($get_all_items_in_inv as $item) {
            $inv_item_amount = $iiaR->repoInvItemAmountquery($item->reqId());
            if (null !== $inv_item_amount) {
                $grand_sub_total = $grand_sub_total
                        + ($inv_item_amount->getSubtotal() ?? 0.00);
                $grand_taxtotal = $grand_taxtotal
                        + ($inv_item_amount->getTaxTotal() ?? 0.00);
                $grand_discount = $grand_discount
                        + ($inv_item_amount->getDiscount() ?? 0.00);
                $grand_charge = $grand_charge
                        + ($inv_item_amount->getCharge() ?? 0.00);
                $grand_allowance = $grand_allowance
                        + ($inv_item_amount->getAllowance() ?? 0.00);
                $grand_total = $grand_total
                        + ($inv_item_amount->getTotal() ?? 0.00);
                $totals = [
                    'subtotal' => $grand_sub_total,
                    'tax_total' => $grand_taxtotal,
                    'discount' => $grand_discount,
                    'charge' => $grand_charge,
                    'allowance' => $grand_allowance,
                    'total' => $grand_total,
                ];
            }
        }
        return $totals;
    }

    /**
     * @param int $quote_id
     * @param float $quote_total
     * @param QR $qR
     * @return float
     */
    public function quoteIncludeCustomerDiscountRequest(
                            int $quote_id, float $quote_total, QR $qR): float
    {
        $quote = $qR->repoQuoteUnloadedquery($quote_id);
        $total = $quote_total;
        $discount_amount = 0.00;
        if ($quote) {
            $discount_amount = (float) $quote->getDiscountAmount();
        }
// Subtract Quote Table's discount amount from Quote Amount Table's quote_total
// Discount and Percent are mutually exclusive ie. if you use the one you
// exclude the other. Discount amount is the user inputed amount on the quote
// representing a cash discount. Discount percent is the user inputed
// percentage on the quote representing a cash percentage
        return $total - $discount_amount;
    }

    /**
     * @param int $salesorder_id
     * @param float $salesorder_total
     * @param SOR $soR
     * @return float
     */
    public function salesorderIncludeCustomerDiscountRequest(
        int $salesorder_id, float $salesorder_total, SOR $soR): float
    {
        $salesorder = $soR->repoSalesOrderUnloadedquery($salesorder_id);
        $total = $salesorder_total;
        $discount_amount = 0.00;
        if ($salesorder) {
            $discount_amount = (float) $salesorder->getDiscountAmount();
        }
        return $total - $discount_amount;
    }

    /**
     * @param int $inv_id
     * @param $inv_total
     * @param IR $iR
     * @return float
     */
    public function invIncludeCustomerDiscountRequest(
                                int $inv_id, float $inv_total, IR $iR): float
    {
        $inv = $iR->repoInvUnloadedquery($inv_id);
        $discount_amount = 0.00;
        $total = $inv_total;
        if ($inv) {
            $discount_amount = (float) $inv->getDiscountAmount();
        }
// Subtract Invoice Table's discount amount from Invoice Amount Table's inv_total
// Discount and Percent are mutually exclusive ie. if you use the one you
// exclude the other. Discount amount is the user inputed amount on the invoice
// representing a cash discount. Discount percent is the user inputed
// percentage on the invoice representing a cash percentage
        return $total - $discount_amount;
    }

    /**
     * Related logic: see QuoteController function defaultTaxQuote
     * @param int $quote_id
     */
    public function calculateQuoteTaxes(
                                  int $quote_id, QTRR $qtrR, QAR $qaR): float
    {
// Quote amount Table fields:
//  id->quote_id->item_subtotal->item_tax_total->tax_total*->total
// Quote Tax Rate Table fields:
//  id->quote_id->tax_rate_id->include_item_tax->quote_tax_rate_amount*

// Tax_total*    =    sum of quote_tax_rate_amount*   per   quote_id.

// First check to see if there are any quote taxes applied
        $total_quote_tax_rate_amount = 0.00;
        $quote_tax_rates = $qtrR->repoQuotequery($quote_id);
        $quote_tax_rates_count = $qtrR->repoCount($quote_id);
// At least one quote tax rate has been set and the quote has amounts that
//  quote tax rates can be applied to
        if (($quote_tax_rates_count > 0) && ($qaR->repoQuoteAmountCount(
                $quote_id) > 0)) {
            // There are quote taxes applied
            $quote_amount = $qaR->repoQuotequery($quote_id);
            if ($quote_amount) {
// Loop through the quote taxes and update quote_tax_rate_amount for each of
//  the applied quote taxes
                /** @var QuoteTaxRate $quote_tax_rate */
                foreach ($quote_tax_rates as $quote_tax_rate) {
                    // If the include item tax has been checked
                    $quote_tax_rate_amount = (
                        (null !== $quote_tax_rate->getIncludeItemTax()
                                && $quote_tax_rate->getIncludeItemTax() === 1)
                    // The quote tax rate should include the applied item tax
? ((($quote_amount->getItemSubtotal() ?? 0.00)
+ ($quote_amount->getItemTaxTotal() ?? 0.00))
* (($quote_tax_rate->getTaxRate()?->getTaxRatePercent() ?? 0.00)  / 100.00))
// The quote tax rate should not include the applied item tax so get the general
//  tax rate from Tax Rate table
: (($quote_amount->getItemSubtotal() ?? 0.00)
* (($quote_tax_rate->getTaxRate()?->getTaxRatePercent() ?? 0.00) / 100.00)));
                    // Update the quote tax rate amount
                    $quote_tax_rate->setQuoteTaxRateAmount(
                                                        $quote_tax_rate_amount);
                    $qtrR->save($quote_tax_rate);
                    $total_quote_tax_rate_amount += $quote_tax_rate_amount;
                }
            }
        }
        return $total_quote_tax_rate_amount;
    }

    /**
     * Related logic: see SalesOrderController function defaultTaxSalesorder
     * @param int $salesorder_id
     */
    public function calculateSalesorderTaxes(
        int $salesorder_id, SOTRR $sotrR, SOAR $soaR): float
    {
        $total_salesorder_tax_rate_amount = 0.00;
        $salesorder_tax_rates = $sotrR->repoSalesOrderquery($salesorder_id);
        $salesorder_tax_rates_count = $sotrR->repoCount($salesorder_id);
        if (($salesorder_tax_rates_count > 0)
            && ($soaR->repoSalesOrderAmountCount($salesorder_id) > 0)) {
            $salesorder_amount = $soaR->repoSalesOrderquery($salesorder_id);
            if ($salesorder_amount) {
                /** @var SalesOrderTaxRate $salesorder_tax_rate */
                foreach ($salesorder_tax_rates as $salesorder_tax_rate) {
                    $salesorder_tax_rate_amount = (
                        (null !== $salesorder_tax_rate->getIncludeItemTax()
                           && $salesorder_tax_rate->getIncludeItemTax() === 1)
                            ? ((($salesorder_amount->getItemSubtotal() ?? 0.00)
                            + ($salesorder_amount->getItemTaxTotal() ?? 0.00))
                    * (($salesorder_tax_rate->getTaxRate()?->getTaxRatePercent()
                                ?? 0.00)  / 100.00))
                            : (($salesorder_amount->getItemSubtotal() ?? 0.00)
                    * (($salesorder_tax_rate->getTaxRate()?->getTaxRatePercent()
                                ?? 0.00) / 100.00))
                    );
                    $salesorder_tax_rate->setSalesOrderTaxRateAmount(
                        $salesorder_tax_rate_amount);
                    $sotrR->save($salesorder_tax_rate);
                    $total_salesorder_tax_rate_amount +=
                        $salesorder_tax_rate_amount;
                }
            }
        }
        return $total_salesorder_tax_rate_amount;
    }


    /**
     * Related logic: see InvController function defaultTaxInv
     * @param $inv_id
     */
    public function calculateInvTaxes(int $inv_id, ITRR $itrR, IAR $iaR): float
    {
// Invoice amount Table fields:
//  id->inv_id->item_subtotal->item_tax_total->tax_total*->total
// Invoice Tax Rate Table fields:
//  id->inv_id->tax_rate_id->include_item_tax->inv_tax_rate_amount*

// Tax_total*    =    sum of inv_tax_rate_amount*   per   inv_id.

// First check to see if there are any invoice taxes applied
        $total_inv_tax_rate_amount = 0.00;
        $inv_tax_rates = $itrR->repoInvquery($inv_id);
        $inv_tax_rates_count = $itrR->repoCount($inv_id);
        if ($inv_tax_rates_count <= 0 || $iaR->repoInvAmountCount($inv_id) <= 0) {
            return $total_inv_tax_rate_amount;
        }
        $inv_amount = $iaR->repoInvquery($inv_id);
        if (!$inv_amount) {
            return $total_inv_tax_rate_amount;
        }
        /** @var InvTaxRate $inv_tax_rate */
        foreach ($inv_tax_rates as $inv_tax_rate) {
            $item_subtotal = $inv_amount->getItemSubtotal() ?: 0.00;
            $tax_rate_percent = $inv_tax_rate->getTaxRate()?->getTaxRatePercent() ?? 0.00;
            if (null !== $inv_tax_rate->getIncludeItemTax()
                && $inv_tax_rate->getIncludeItemTax() === 1) {
                $item_tax_total = $inv_amount->getItemTaxTotal() ?: 0.00;
                $inv_tax_rate_amount = ($item_subtotal + $item_tax_total) * $tax_rate_percent / 100.00;
            } else {
                $inv_tax_rate_amount = $item_subtotal * ($tax_rate_percent / 100.00);
            }
            $inv_tax_rate->setInvTaxRateAmount($inv_tax_rate_amount);
            $itrR->save($inv_tax_rate);
            $total_inv_tax_rate_amount += $inv_tax_rate_amount;
        }
        return $total_inv_tax_rate_amount;
    }

    /**
     * @return array
     */
    public function recurFrequencies(): array
    {
        return [
            '1D' => 'calendar.day.1',
            '2D' => 'calendar.day.2',
            '3D' => 'calendar.day.3',
            '4D' => 'calendar.day.4',
            '5D' => 'calendar.day.5',
            '6D' => 'calendar.day.6',
            '15D' => 'calendar.day.15',
            '30D' => 'calendar.day.30',
            '7D' => 'calendar.week.1',
            '14D' => 'calendar.week.2',
            '21D' => 'calendar.week.3',
            '28D' => 'calendar.week.4',
            '1M' => 'calendar.month.1',
            '2M' => 'calendar.month.2',
            '3M' => 'calendar.month.3',
            '4M' => 'calendar.month.4',
            '5M' => 'calendar.month.5',
            '6M' => 'calendar.month.6',
            '7M' => 'calendar.month.7',
            '8M' => 'calendar.month.8',
            '9M' => 'calendar.month.9',
            '10M' => 'calendar.month.10',
            '11M' => 'calendar.month.11',
            '1Y' => 'calendar.year.1',
            '2Y' => 'calendar.year.2',
            '3Y' => 'calendar.year.3',
            '4Y' => 'calendar.year.4',
            '5Y' => 'calendar.year.5',
        ];
    }
}
