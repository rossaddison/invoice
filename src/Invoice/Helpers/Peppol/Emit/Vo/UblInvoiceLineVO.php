<?php

declare(strict_types=1);

namespace App\Invoice\Helpers\Peppol\Emit\Vo;

readonly class UblInvoiceLineVO
{
    /**
     * @param UblAllowanceChargeVO[] $allowanceCharges
     */
    public function __construct(
        public string  $id,
        public ?string $note,
        public float   $invoicedQuantity,
        public ?string $invoicedQuantityUnitCode,
        public float   $lineExtensionAmount,
        public string  $lineExtensionAmountCurrencyId,
        public ?string $accountingCost,
        public ?string $orderLineReferenceLineId,
        public ?string $itemName,
        public ?string $itemDescription,
        public ?string $itemSellerAssignedId,
        public ?string $itemBuyerAssignedId,
        public ?string $itemStandardId,
        public ?string $itemStandardIdSchemeId,
        public ?string $itemClassificationCode,
        public ?string $itemClassificationCodeSchemeId,
        public ?string $itemTaxCategoryId,
        public ?float  $itemTaxCategoryPercent,
        public ?float  $priceAmount,
        public ?float  $priceBaseQuantity,
        public ?string $priceBaseQuantityUnitCode,
        public array   $allowanceCharges,
    ) {}
}
