<?php

declare(strict_types=1);

namespace App\Invoice\Helpers\StoreCove;

use App\Infrastructure\Persistence\Inv\Inv;
use App\Infrastructure\Persistence\InvAllowanceCharge\InvAllowanceCharge;
use App\Infrastructure\Persistence\InvItem\InvItem;
use App\Infrastructure\Persistence\InvItemAllowanceCharge\InvItemAllowanceCharge;
use App\Infrastructure\Persistence\InvItemAmount\InvItemAmount;
use App\Infrastructure\Persistence\TaxRate\TaxRate;
use App\Invoice\InvAllowanceCharge\InvAllowanceChargeRepository as ACIR;
use App\Invoice\InvItemAllowanceCharge\InvItemAllowanceChargeRepository as ACIIR;
use App\Invoice\InvItemAmount\InvItemAmountRepository as IIAR;
use App\Invoice\ProductProperty\ProductPropertyRepository as ppR;
use App\Invoice\SalesOrderItem\SalesOrderItemRepository as SOIR;
use App\Invoice\TaxRate\TaxRateRepository as TRR;
use App\Invoice\UnitPeppol\UnitPeppolRepository as unpR;
use App\Invoice\Setting\SettingRepository as SRepo;
use App\Invoice\Ubl\InvoicePeriod;
use App\Invoice\Helpers\Peppol\Exception\{
    PeppolClientNotFoundException,
    PeppolSalesOrderItemNotExistException,
    PeppolSalesOrderItemPurchaseOrderItemNumberNotExistException,
    PeppolSalesOrderItemPurchaseOrderLineNumberNotExistException,
    PeppolTaxCategoryCodeNotFoundException,
    PeppolTaxCategoryPercentNotFoundException,
};
use Yiisoft\Translator\TranslatorInterface as Translator;

final readonly class StoreCoveInvoiceLineBuilder
{
    private const string SETTING_CURRENCY_CODE_FROM = 'currency_code_from';

    public function __construct(
        private SRepo $s,
        private Translator $t,
    ) {}

    /**
     * @param Inv $invoice
     * @param InvoicePeriod $invoice_period
     * @param StoreCoveHelperInvDeps $inv
     * @param StoreCoveHelperNetDeps $net
     * @param StoreCoveHelperChargeDeps $charge
     * @throws PeppolClientNotFoundException
     * @return array
     */
    public function buildInvoiceLinesArray(
        Inv $invoice,
        InvoicePeriod $invoice_period,
        StoreCoveHelperInvDeps $inv,
        StoreCoveHelperNetDeps $net,
        StoreCoveHelperChargeDeps $charge,
    ): array
    {
        $client = $invoice->getClient();
        if ($client) {
            $client_peppol = $inv->cpR->repoClientPeppolLoadedquery($client->reqId());
            if ($client_peppol) {
                $invoiceLines = [];
                /**
                 * @var InvItem $item
                 */
                foreach ($invoice->getItems() as $item) {
                    $price = ($item->getPrice() ?? 0.00);
                    $peppol_po_itemid = $this->peppolPoItemid($item, $charge->soiR);
                    $peppol_po_lineid = $this->peppolPoLineid($item, $charge->soiR);
                    $item_id = $item->reqId();
                    $product_properties_array =
                            $this->buildProductPropertyArray($item_id, $net->ppR);
                    $inv_item_amount = $this->getInvItemAmount($item_id, $inv->iiaR);
                    if (isset($inv_item_amount)) {
                        $invoiceLines[$item_id] = [
                            'lineId' => $item_id,
                            'amountExcludingVat' => '',
                            'itemPrice' => $this->s->currencyConverter($price),
                            'baseQuantity' =>
                    $item->getProduct()?->getProductPriceBaseQuantity(),
                            'quantity' => $item->getQuantity(),
                            'quantityUnitCode' => $this->unitCode(
                                $item->getProduct()?->getUnit()?->reqId(),
                                $net->unpR),
                            'tax' => [
                                'percentage' =>
                   $item->getProduct()?->getTaxRate()?->getTaxRatePercent(),
                                'country' =>
                  $item->getProduct()?->getProductCountryOfOriginCode(),
                                'category' =>
                 $item->getProduct()?->getTaxRate()?->getStoreCoveTaxType(),
                            ],
                            'orderLineReferenceLineId' =>
                        $peppol_po_lineid ?? $this->t->translate('client.'),
                            'accountingCost' =>
                        $client_peppol->getAccountingCost(),
                            'name' => $item->getName(),
                            'description' => $item->getDescription(),
                            'invoicePeriod' =>
                        $invoice_period->getStartDate()
                                . ' - ' . $invoice_period->getEndDate(),
                            'note' => $item->getNote(),
                            'references' => [
                            ],
                            'buyersItemIdentification' => $peppol_po_itemid,
                            'sellersItemIdentification' =>
                                $item->getProduct()?->getProductSku(),
                            'standardItemIdentification' =>
                                $item->getProduct()?->getProductSiiId(),
                            'standardItemIdentificationSchemeId' =>
                             $item->getProduct()?->getProductSiiSchemeid(),
                            'additionalItemProperties' => [
                                0 => [
                                    'name' =>
           $item->getProduct()?->getProductAdditionalItemPropertyName(),
                                    'value' =>
          $item->getProduct()?->getProductAdditionalItemPropertyValue(),
                                ],
                                $product_properties_array,
                            ],
                        ];
                        $inv_item_allowance_charges =
                                $charge->aciiR->repoInvItemquery($item_id);
                        /**
                         * @var InvItemAllowanceCharge $acii
                         */
                        foreach ($inv_item_allowance_charges as $acii) {
                            $invoiceLines[$item_id]['allowanceCharges'][] = [
                                'reason' =>
                                   $acii->getAllowanceCharge()?->getReason(),
                                'amountExcludingTax' =>
                                $acii->getAllowanceCharge()?->getBaseAmount(),
                            ];
                        }
                    }
                }
                return $invoiceLines;
            }
            throw new PeppolClientNotFoundException($this->t);
        } else {
            throw new PeppolClientNotFoundException($this->t);
        }
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
        $allowanceCharges = [];
        if ($aciR->repoACICount($invoice_id)) {
            /**
             * @var InvAllowanceCharge $ac
             */
            foreach ($allowances_or_charges as $ac) {
                $allowanceCharges[] = [
                    'reason' => $ac->getAllowanceCharge()?->getReason(),
                    'amountExcludingTax' => $ac->getAmount(),
                    'baseAmountExcludingTax' =>
                        $ac->getAllowanceCharge()?->getBaseAmount(),
                    'tax' => [
                        'percentage' =>
            $ac->getAllowanceCharge()?->getTaxRate()?->getTaxRatePercent(),
                        'country' =>
                                $this->s->getSetting(self::SETTING_CURRENCY_CODE_FROM),
                        'category' =>
            $ac->getAllowanceCharge()?->getTaxRate()?->getStorecoveTaxType(),
                    ],
                ];
            }
        }
        return $allowanceCharges;
    }

    /**
     * @param Inv $invoice
     * @param IIAR $iiaR
     * @param TRR $trR
     * @throws PeppolTaxCategoryCodeNotFoundException
     * @throws PeppolTaxCategoryPercentNotFoundException
     * @return array
     */
    public function buildTaxSubtotalArray(Inv $invoice, IIAR $iiaR, TRR $trR): array
    {
        $array = [];
        $taxRates = $trR->findAllPreloaded();
        /**
         * @var TaxRate $taxRate
         */
        foreach ($taxRates as $taxRate) {
            $id = $taxRate->reqId();
            $tax_category = $taxRate->getPeppolTaxRateCode();
            $tax_percent = $taxRate->getTaxRatePercent();
            if (null == $tax_category) {
                throw new PeppolTaxCategoryCodeNotFoundException($this->t);
            }
            if (null === $tax_percent) {
                throw new PeppolTaxCategoryPercentNotFoundException($this->t);
            }

            $taxable_amount_total = 0.00;
            $tax_amount_total = 0.00;
            $items = $invoice->getItems();
            /**
             * @var InvItem $item
             */
            foreach ($items as $item) {
                $item_id = $item->reqId();
                if ($id === $item->getTaxRate()?->reqId()) {
                    $item_amount = $iiaR->repoInvItemAmountquery($item_id);
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

            /**
             * @var array $array[$id]
             */
            $sub_array = $array[$id] ?? [];
            /**
             *  @var float $sub_array['TaxableAmounts']
             */
            $sub_array['TaxableAmounts'] = (float) $this->s->currencyConverter(
                                                     $taxable_amount_total);
            /**
             *  @var float $sub_array['TaxAmount']
             */
            $sub_array['TaxAmount'] = (float) $this->s->currencyConverter(
                                                        $tax_amount_total);
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
            $sub_array['DocumentCurrency'] = $this->s->getSetting('currency_code_to');
            $array[$id] = $sub_array;
        }
        return $array;
    }

    /**
     * @param int $item_id
     * @param IIAR $iiaR
     * @return InvItemAmount|null
     */
    public function getInvItemAmount(int $item_id, IIAR $iiaR): ?InvItemAmount
    {
        return $iiaR->repoInvItemAmountquery($item_id);
    }

    /**
     * @param InvItem $item
     * @param SOIR $soiR
     * @throws PeppolSalesOrderItemPurchaseOrderItemNumberNotExistException
     * @throws PeppolSalesOrderItemNotExistException
     * @return string|null
     */
    private function peppolPoItemid(InvItem $item, SOIR $soiR): ?string
    {
        $sales_order_item_id = $item->getSoItemId();
        if ($sales_order_item_id > 0) {
            $sales_order_item = $soiR->repoSalesOrderItemquery($sales_order_item_id);
            if (null !== $sales_order_item) {
                $peppol_po_itemid = $sales_order_item->getPeppolPoItemid();
                if (null !== $peppol_po_itemid) {
                    return $peppol_po_itemid;
                }
throw new PeppolSalesOrderItemPurchaseOrderItemNumberNotExistException($this->t);
            } else {
throw new PeppolSalesOrderItemNotExistException($this->t);
            }
        }
        return null;
    }

    /**
     * @param InvItem $item
     * @param SOIR $soiR
     * @throws PeppolSalesOrderItemPurchaseOrderLineNumberNotExistException
     * @throws PeppolSalesOrderItemNotExistException
     * @return string|null
     */
    private function peppolPoLineid(InvItem $item, SOIR $soiR): ?string
    {
        $sales_order_item_id = $item->getSoItemId();
        if ($sales_order_item_id > 0) {
            $sales_order_item = $soiR->repoSalesOrderItemquery($sales_order_item_id);
            if (null !== $sales_order_item) {
                $peppol_po_lineid = $sales_order_item->getPeppolPoLineid();
                if (null !== $peppol_po_lineid) {
                    return $peppol_po_lineid;
                }
                throw new PeppolSalesOrderItemPurchaseOrderLineNumberNotExistException(
                        $this->t);
            } else {
                throw new PeppolSalesOrderItemNotExistException($this->t);
            }
        }
        return null;
    }

    /**
     * @param int|null $unit_id
     * @param unpR $unpR
     * @return string|null
     */
    private function unitCode(?int $unit_id, unpR $unpR): ?string
    {
        if (null !== $unit_id && ($unpR->repoUnitCount($unit_id) == 1)) {
            $unit_peppol = $unpR->repoUnit($unit_id);
            return $unit_peppol?->getCode();
        }
        return '';
    }

    /**
     * @param int $product_id
     * @param ppR $ppR
     * @return array
     */
    private function buildProductPropertyArray(int $product_id, ppR $ppR): array
    {
        $product_propertys = $ppR->findAllProduct($product_id);
        $product_property_array = [];
        $i = 1;
        /**
         * @var \\App\Infrastructure\Persistence\ProductProperty\ProductProperty $product_property
         */
        foreach ($product_propertys as $product_property) {
            $product_property_array[$i] = [
                'name' => $product_property->getName(),
                'value' => $product_property->getValue(),
            ];
            $i += 1;
        }
        return $product_property_array;
    }
}
