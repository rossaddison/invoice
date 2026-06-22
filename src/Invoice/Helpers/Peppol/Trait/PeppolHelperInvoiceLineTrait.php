<?php

declare(strict_types=1);

namespace App\Invoice\Helpers\Peppol\Trait;

use App\Infrastructure\Persistence\{Inv\Inv, InvItem\InvItem,
    InvItemAllowanceCharge\InvItemAllowanceCharge, UnitPeppol\UnitPeppol};
use App\Invoice\ClientPeppol\ClientPeppolRepository as cpR;
use App\Invoice\InvItemAllowanceCharge\InvItemAllowanceChargeRepository as ACIIR;
use App\Invoice\InvItemAmount\InvItemAmountRepository as IIAR;
use App\Invoice\SalesOrderItem\SalesOrderItemRepository as SOIR;
use App\Invoice\UnitPeppol\UnitPeppolRepository as unpR;
use App\Invoice\Ubl\{InvoicePeriod, Schema};
use App\Invoice\Helpers\Peppol\Exception\{
    PeppolClientNotFoundException as ClientNf,
    PeppolProductUnitCodeNotFoundException as ProductUnitCodeNf,
    PeppolProductItemClassificationCodeSchemeIdNotFoundException as PICCSINf,
    PeppolSalesOrderItemPurchaseOrderItemNumberNotExistException as SOIPOINNe,
    PeppolSalesOrderItemPurchaseOrderLineNumberNotExistException as SOIPOLNNe,
    PeppolSalesOrderItemNotExistException as SOINe,
};

trait PeppolHelperInvoiceLineTrait
{
    /**
     * @param Inv $invoice
     * @param InvoicePeriod $invoice_period
     * @param IIAR $iiaR
     * @param cpR $cpR
     * @param SOIR $soiR
     * @param ACIIR $aciiR
     * @param unpR $unpR
     * @throws ProductUnitCodeNf
     * @throws SOIPOINNe
     * @throws ClientNf
     * @return array
     */
    private function buildInvoiceLinesArray(Inv $invoice,
        InvoicePeriod $invoice_period, IIAR $iiaR, cpR $cpR, SOIR $soiR,
                                                ACIIR $aciiR, unpR $unpR): array
    {
        $client = $invoice->getClient();
        if (!$client) {
            throw new ClientNf($this->t);
        }
        $client_peppol = $cpR->repoClientPeppolLoadedquery($client->reqId());
        if (!$client_peppol) {
            throw new ClientNf($this->t);
        }
        $invoiceLines = [];
        $b = Schema::CBC;
        $a = Schema::CAC;
        /** @var InvItem $item */
        foreach ($invoice->getItems() as $item) {
            [$peppol_po_itemid, $peppol_po_lineid] = $this->validateInvItem($item, $soiR);
            $item_id = $item->reqId();
            $inv_item_amount = $this->getInvItemAmount($item_id, $iiaR);
            if (!isset($inv_item_amount)) {
                continue;
            }
            $sub_total = $inv_item_amount->getSubtotal() ?? 0;
            $convert_sub_total = $this->s->currencyConverter($sub_total);
            $unit_peppol_id = $item->getProduct()?->getUnitPeppolId();
            if (null === $unit_peppol_id) {
                continue;
            }
            $unit_peppol = $unpR->repoUnitPeppolLoadedquery($unit_peppol_id);
            if (null === $unit_peppol) {
                continue;
            }
            $price = $item->getPrice() ?? 0.00;
            $discount = $item->getDiscountAmount() ?? 0.00;
            $optionals = $this->buildOptionalInvoiceLineElements(
                $item, $peppol_po_lineid, $peppol_po_itemid
            );
            $lineNote = $optionals['lineNote'];
            $itemDesc = $optionals['itemDesc'];
            $originCountry = $optionals['originCountry'];
            $orderLineRef = $optionals['orderLineRef'];
            $buyersItemId = $optionals['buyersItemId'];
            $invoiceLines[$item_id] = [
                'name' => "{$a}InvoiceLine",
                'value' => [
                    ['name' => "{$b}ID", 'value' => (string) $item_id],
                    ...$lineNote,
                    [
                        'name' => "{$b}InvoicedQuantity",
                        'value' => (string) $item->getQuantity(),
                        'attributes' => ['unitCode' => $unit_peppol->getCode()],
                    ],
                    [
                        'name' => "{$b}LineExtensionAmount",
                        'value' => $convert_sub_total,
                        'attributes' => ['currencyID' => $this->documentCurrency],
                    ],
                    ['name' => "{$b}AccountingCost", 'value' =>
                        $client_peppol->getAccountingCost()],
                    [
                        'name' => "{$a}InvoicePeriod",
                        'value' => [
                            ['name' => "{$b}StartDate", 'value' =>
                                $invoice_period->getStartDate()],
                            ['name' => "{$b}EndDate", 'value' =>
                                $invoice_period->getEndDate()],
                        ],
                    ],
                    ...$orderLineRef,
                    [
                        'name' => "{$a}DocumentReference",
                        'value' => [
                            ['name' => "{$b}ID", 'value' => $invoice->getNumber()],
                            ['name' => "{$b}DocumentTypeCode", 'value' => '130'],
                        ],
                    ],
                    $this->itemLineACs($aciiR, $item_id),
                    $this->buildInvoiceLineItemElement($item, $itemDesc,
                        $buyersItemId, $originCountry),
                    $this->buildInvoiceLinePriceElement($item, $unit_peppol,
                        $price, $discount),
                ],
            ];
        }
        return $invoiceLines;
    }

    /** @return array{0: ?string, 1: ?string} */
    private function validateInvItem(InvItem $item, SOIR $soiR): array
    {
        $product = $item->getProduct();
        if (null !== $product && $product->getUnitPeppolId() <= 0) {
            throw new ProductUnitCodeNf($this->t, $product);
        }
        $peppol_po_itemid = $this->peppolPoItemid($item, $soiR);
        if (null == $peppol_po_itemid && $item->getSoItemId() > 0) {
            throw new SOIPOINNe($this->t);
        }
        $peppol_po_lineid = $this->peppolPoLineid($item, $soiR);
        if (null == $peppol_po_lineid && $item->getSoItemId() > 0) {
            throw new SOIPOLNNe($this->t);
        }
        $listid = $product?->getProductIccListid();
        if (null == $listid && null !== $product) {
            throw new PICCSINf($this->t, $product);
        }
        return [$peppol_po_itemid, $peppol_po_lineid];
    }

    /**
     * @psalm-return array{lineNote: array, itemDesc: array, originCountry: array, orderLineRef: array, buyersItemId: array}
     */
    private function buildOptionalInvoiceLineElements(
        InvItem $item,
        ?string $peppol_po_lineid,
        ?string $peppol_po_itemid,
    ): array {
        $a = Schema::CAC;
        $b = Schema::CBC;
        $lineDesc = $item->getDescription() ?? '';
        $lineNote = $lineDesc !== ''
            ? [['name' => "{$b}Note", 'value' => $lineDesc]]
            : [];
        $itemDesc = $lineDesc !== ''
            ? [['name' => "{$b}Description", 'value' => $lineDesc]]
            : [];
        $originCode = $item->getProduct()?->getProductCountryOfOriginCode() ?? '';
        $originCountry = $originCode !== ''
            ? [['name' => "{$a}OriginCountry",
                'value' => [['name' => "{$b}IdentificationCode",
                             'value' => $originCode]]]]
            : [];
        $orderLineRef = ($peppol_po_lineid !== null && $peppol_po_lineid !== '')
            ? [['name' => "{$a}OrderLineReference",
                'value' => [['name' => "{$b}LineID",
                             'value' => $peppol_po_lineid]]]]
            : [];
        $buyersItemId = ($peppol_po_itemid !== null && $peppol_po_itemid !== '')
            ? [['name' => "{$a}BuyersItemIdentification",
                'value' => [['name' => "{$b}ID",
                             'value' => $peppol_po_itemid]]]]
            : [];
        return [
            'lineNote' => $lineNote,
            'itemDesc' => $itemDesc,
            'originCountry' => $originCountry,
            'orderLineRef' => $orderLineRef,
            'buyersItemId' => $buyersItemId,
        ];
    }

    private function buildInvoiceLineItemElement(
        InvItem $item,
        array $itemDesc,
        array $buyersItemId,
        array $originCountry,
    ): array {
        $a = Schema::CAC;
        $b = Schema::CBC;
        return [
                            'name' => "{$a}Item",
                            'value' => [
                                ...$itemDesc,
                                [
                                    'name' => "{$b}Name",
                                    'value' => $item->getName()
                                ],
                                ...$buyersItemId,
                                [
                                    'name' => "{$a}SellersItemIdentification",
                                    'value' => [
                                        [
                                            'name' => "{$b}ID",
                                            'value' =>
                                        $item->getProduct()?->getProductSku()
                                        ],
                                    ],
                                ],
                                [
                                    'name' => "{$a}StandardItemIdentification",
                                    'value' => [
                                        [
                                            'name' => "{$b}ID",
                                            'value' =>
                                       $item->getProduct()?->getProductSiiId(),
                                            'attributes' => [
                                                'schemeID' =>
                                $item->getProduct()?->getProductSiiSchemeid(),
                                            ],
                                        ],
                                    ],
                                ],
                                ...$originCountry,
                                [
                                    'name' => "{$a}CommodityClassification",
                                    'value' => [
                                        [
                                            'name' => "{$b}ItemClassificationCode",
                                            'value' =>
                                    $item->getProduct()?->getProductIccId(),
                                            'attributes' => [
                                                'listID' =>
                                    $item->getProduct()?->getProductIccListid(),
                                                'listVersionID' =>
                            $item->getProduct()?->getProductIccListversionid(),
                                            ],
                                        ],
                                    ],
                                ],
                                [
                                    'name' => "{$a}ClassifiedTaxCategory",
                                    'value' => [
                                        [
                                            'name' => "{$b}ID",
                                            'value' =>
            $item->getTaxRate()?->getPeppolTaxRateCode()
                                        ],
                                        [
                                            'name' => "{$b}Percent",
                                            'value' =>
            $item->getTaxRate()?->getTaxRatePercent()
                                        ],
                                        [
                                            'name' => "{$a}TaxScheme",
                                            'value' => [
                                                [
                                                    'name' => "{$b}ID",
                                                    'value' => self::TAX_CATEGORY_VAT
                                                ],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                            [
                                'name' => "{$a}AdditionalItemProperty",
                                'value' => [
                                    [
                                        'name' => "{$b}Name",
                                        'value' =>
            $item->getProduct()?->getProductAdditionalItemPropertyName()
                                    ],
                                    [
                                        'name' => "{$b}Value",
                                        'value' =>
            $item->getProduct()?->getProductAdditionalItemPropertyValue()
                                    ],
                                ],
                            ],
        ];
    }

    private function buildInvoiceLinePriceElement(
        InvItem $item,
        UnitPeppol $unit_peppol,
        float $price,
        float $discount,
    ): array {
        $a = Schema::CAC;
        $b = Schema::CBC;
        return [
                            'name' => "{$a}Price",
                            'value' => [
                                [
                                    'name' => "{$b}PriceAmount",
                                    'value' =>
                                            $this->s->currencyConverter($price),
                                    'attributes' => [
                                        'currencyID' =>
                                        $this->documentCurrency
                                    ]
                                ],
                                [
                                    'name' => "{$b}BaseQuantity",
                                    'value' => $item->getQuantity(),
                                    'attributes' => [
                                        'unitCode' => $unit_peppol->getCode()
                                    ]
                                ],
                                [
                                    'name' => "{$a}AllowanceCharge",
                                    'value' => [
                                        [
                                            'name' => "{$b}ChargeIndicator",
                                            'value' => 'false'
                                        ],
                                        [
                                            'name' => "{$b}Amount",
                                            'value' =>
                                        $this->s->currencyConverter($discount),
                                            'attributes' => [
                                                'currencyID' =>
            $this->documentCurrency
                                            ]
                                        ],
                                        [
                                            'name' => "{$b}BaseAmount",
                                            'value' =>
            $this->s->currencyConverter($price), 'attributes' => [
                                            'currencyID' =>
            $this->documentCurrency]
                                        ],
                                    ],
                                ],
                            ],
        ];
    }

    private function itemLineACs(ACIIR $aciiR, int $itemId): array
    {
        $aciis = $aciiR->repoInvItemquery($itemId);
        $a = Schema::CAC;
        $b = Schema::CBC;
        $itemLine = [];
        /**
         * @var InvItemAllowanceCharge $acii
         */
        foreach ($aciis as $acii) {
            $itemLine[] =
            [
                'name' =>
                "{$a}AllowanceCharge",
                'value' => [
                    ['name' =>
                        "{$b}ChargeIndicator",
                        'value' => $acii->getAllowanceCharge()?->getIdentifier()
                                                        == 0 ? 'false' : 'true'
                    ],
                    ['name' =>
                        "{$b}AllowanceChargeReasonCode",
                        'value' => $acii->getAllowanceCharge()?->getReasonCode()],
                    ['name' =>
                        "{$b}AllowanceChargeReason",
                        'value' => $acii->getAllowanceCharge()?->getReason()],
                    ['name' =>
                        "{$b}MultiplierFactorNumeric",
                        'value' =>
                     $acii->getAllowanceCharge()?->getMultiplierFactorNumeric()],
                    [
                        'name' => "{$b}Amount",
                        'value' => $this->s->currencyConverter($acii->getAmount()),
                        'attributes' => [
                            'currencyID' =>
                                $this->documentCurrency
                        ]
                    ],
                    [
                        'name' => "{$b}BaseAmount",
                        'value' =>
    $this->s->currencyConverter($acii->getAllowanceCharge()?->getBaseAmount()
            ?? 0.00),
                        'attributes' => [
                            'currencyID' =>
                                $this->documentCurrency
                        ]
                    ],
                ],
            ];
        }
        return $itemLine;
    }

    /**
     * Retrieve the Client/Customer's purchase order item id
     * @param InvItem $item
     * @param SOIR $soiR
     * @throws SOIPOINNe
     * @throws SOINe
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
                throw new SOIPOINNe($this->t);
            } else {
                throw new SOINe($this->t);
            }
        }
        $itemid = $item->getPeppolPoItemid();
        return ($itemid !== '' && $itemid !== null) ? $itemid : null;
    }

    /**
     * Retrieve the Client/Customer's purchase order line id
     * @param InvItem $item
     * @param SOIR $soiR
     * @throws SOIPOLNNe
     * @throws SOINe
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
                throw new SOIPOLNNe($this->t);
            } else {
                throw new SOINe($this->t);
            }
        }
        $lineid = $item->getPeppolPoLineid();
        return ($lineid !== '' && $lineid !== null) ? $lineid : null;
    }
}
