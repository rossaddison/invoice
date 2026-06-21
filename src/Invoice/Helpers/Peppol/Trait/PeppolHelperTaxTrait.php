<?php

declare(strict_types=1);

namespace App\Invoice\Helpers\Peppol\Trait;

use App\Infrastructure\Persistence\{Inv\Inv, InvAllowanceCharge\InvAllowanceCharge, InvItem\InvItem};
use App\Invoice\Delivery\DeliveryRepository as DelRepo;
use App\Invoice\InvAllowanceCharge\InvAllowanceChargeRepository as ACIR;
use App\Invoice\InvItemAmount\InvItemAmountRepository as IIAR;
use App\Invoice\TaxRate\TaxRateRepository as TRR;
use App\Invoice\Helpers\Peppol\Exception\{
    PeppolDeliveryLocationIDNotFoundException as DelLocIdNf,
    PeppolInvoicePeriodDetailsIncompleteException as InvPeriodDetIncompleteNf,
    PeppolTaxCategoryCodeNotFoundException as TCCNf,
    PeppolTaxCategoryPercentNotFoundException as TCPNf,
};

trait PeppolHelperTaxTrait
{
    /**
     * @param Inv $invoice
     * @param DelRepo $delRepo
     * @throws InvPeriodDetIncompleteNf
     * @throws DelLocIdNf
     * @return string
     */
    public function descriptionCode(Inv $invoice, DelRepo $delRepo): string
    {
        if ($this->s->getSetting('include_delivery_period') == '1'
                            && !empty($this->s->getSetting('stand_in_code'))) {
            if ((int) $invoice->getDeliveryLocationId() > 0) {
                $delivery = $delRepo->repoInvoicequery($invoice->reqId());
                if ((null !== $delivery)
                        && (!empty($invoice->getStandInCode()))) {
                    $description_code = $invoice->getStandInCode();
                } else {
                    throw new InvPeriodDetIncompleteNf();
                }
            } else {
                throw new DelLocIdNf($this->t);
            }
        } else {
            $description_code = '';
        }
        return $description_code;
    }

    /**
     * @param Inv $invoice
     * @param ACIR $aciR
     * @return array
     */
    public function documentLevelAllowanceCharges(Inv $invoice, ACIR $aciR): array
    {
        $invoice_id = $invoice->reqId();
        $allowances_or_charges = $aciR->repoACIquery($invoice_id);
        $array = [];
        if ($aciR->repoACICount($invoice_id)) {
            /**
             * @var InvAllowanceCharge $ac
             */
            foreach ($allowances_or_charges as $ac) {
                $array[] = [
                    'chargeIndicator' =>
                                $ac->getAllowanceCharge()?->getIdentifier(),
                    'allowanceChargeReasonCode' =>
                                $ac->getAllowanceCharge()?->getReasonCode(),
                    'allowanceChargeReason' =>
                                    $ac->getAllowanceCharge()?->getReason(),
                    'multiplierFactorNumeric' =>
                    $ac->getAllowanceCharge()?->getMultiplierFactorNumeric(),
                    'baseAmount' =>
                                $ac->getAllowanceCharge()?->getBaseAmount(),
                    'amount' => $ac->getAmount(),
                    'taxTotal' => [
                        'doc_cc_tax_amount' => $ac->getVatOrTax(),
                        'doc_cc' => $this->documentCurrency,
                        'supp_tax_cc_tax_amount' =>
                            $this->s->currencyConverter($ac->getVatOrTax() ?? 0.00),
                        'supp_cc' => $this->s->getSetting('currency_code_from'),
                    ],
                    'taxCategory' => [
                        'taxScheme' => [
                            'value' => self::TAX_CATEGORY_VAT,
                        ],
                    ],
                ];
            }
        }
        return $array;
    }

    /**
     * Used later in src\Invoice\Ubl\TaxTotal xmlSerialize
     * @param float $supp_tax_cc_tax_amount
     * @return array
     */
    private function taxAmounts(float $supp_tax_cc_tax_amount): array
    {
        $doc_cc_tax_amount =
                    (float) $this->s->currencyConverter($supp_tax_cc_tax_amount);
        return [
            'supp_tax_cc_tax_amount' => $supp_tax_cc_tax_amount,
            'supp_tax_cc' => $this->from_currency,
            'doc_cc_tax_amount' => $doc_cc_tax_amount,
            'doc_cc' => $this->to_currency,
        ];
    }

    /**
     * @param Inv $invoice
     * @param IIAR $iiaR
     * @param TRR $trR
     * @throws TCCNf
     * @throws TCPNf
     * @return array
     */
    private function buildTaxSubtotalArray(
                                     Inv $invoice, IIAR $iiaR, TRR $trR): array
    {
        $array = [];
        $item_tax_rates = [];
        $taxable_amount_total = 0;
        $tax_amount_total = 0;
        /**
         * @var InvItem $item
         */
        foreach ($invoice->getItems() as $item) {
            if (!in_array($item->reqTaxRateId(), $item_tax_rates)) {
                $item_tax_rates[] = $item->reqTaxRateId();
            }
        }
        foreach ($item_tax_rates as $id) {
            $taxRate = $trR->repoTaxRatequery($id);
            if (null !== $taxRate) {
                $tax_category = $taxRate->getPeppolTaxRateCode();
                $tax_percent = $taxRate->getTaxRatePercent();
                if (null === $tax_category) {
                    throw new TCCNf($this->t);
                }
                if (null === $tax_percent) {
                    throw new TCPNf($this->t);
                }
                if (!empty($id)) {
                    $taxable_amount_total = 0.00;
                    $tax_amount_total = 0.00;
                    $this->sumItemsForTaxRate($invoice, $id, $iiaR, $taxable_amount_total, $tax_amount_total);
                }

                /**
                 * @var array $array[$id]
                 */
                $sub_array = $array[$id] ?? [];
                /**
                 *  @var float $sub_array['TaxableAmounts']
                 */
                $sub_array['TaxableAmounts'] = $taxable_amount_total;
                /**
                 *  @var float $sub_array['TaxAmount']
                 */
                $sub_array['TaxAmount'] = $tax_amount_total;
                /**
                 *  @var float $sub_array['TaxCategory']
                 */
                $sub_array['TaxCategory'] = $tax_category;
                /**
                 *  @var float $sub_array['TaxCategoryPercent']
                 */
                $sub_array['TaxCategoryPercent'] = $tax_percent;
                /**
                 *  @var string $sub_array['DocumentCurrency']
                 */
                $sub_array['DocumentCurrency'] =
                            $this->documentCurrency;
                $array[$id] = $sub_array;
            }
        }
        return $array;
    }

    private function sumItemsForTaxRate(
        Inv $invoice,
        int $id,
        IIAR $iiaR,
        float &$taxable_amount_total,
        float &$tax_amount_total,
    ): void {
        /** @var InvItem $item */
        foreach ($invoice->getItems() as $item) {
            if ($id == $item->getTaxRate()?->reqId()) {
                $item_amount = $iiaR->repoInvItemAmountquery($item->reqId());
                if (null !== $item_amount) {
                    $item_sub_total = $item_amount->getSubtotal();
                    if (null !== $item_sub_total) {
                        $taxable_amount_total += $item_sub_total;
                    }
                    $item_tax_total = $item_amount->getTaxTotal();
                    if (null !== $item_tax_total) {
                        $tax_amount_total += $item_tax_total;
                    }
                }
            }
        }
    }

    /**
     * If the DateTimeImmutable formatted tax point is 1901/01/01, it is NOT a tax point
     * @param Inv $invoice
     * @return bool
     */
    private function noTaxPointDate(Inv $invoice): bool
    {
        $date = $invoice->getDateTaxPoint()->format(self::DATE_FORMAT_YMD);
        return $date === '1901/01/01';
    }
}
