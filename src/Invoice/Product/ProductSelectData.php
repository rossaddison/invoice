<?php

declare(strict_types=1);

namespace App\Invoice\Product;

use App\Infrastructure\Persistence\Family\Family;
use App\Infrastructure\Persistence\TaxRate\TaxRate;
use App\Infrastructure\Persistence\Unit\Unit;
use App\Infrastructure\Persistence\UnitPeppol\UnitPeppol;
use App\Invoice\Family\FamilyRepository as fR;
use Yiisoft\Data\Cycle\Reader\EntityReader;

/**
 * Pure static array-builders for product-form select data.
 * No instance state — all methods are stateless transformations of repository data.
 */
final class ProductSelectData
{
    /** @return array<int, string|null> */
    public static function families(EntityReader $families): array
    {
        $array = [];
        /** @var Family $family */
        foreach ($families as $family) {
            $array[$family->reqId()] = $family->getFamilyName();
        }
        return $array;
    }

    /** @return array<int, string> */
    public static function units(EntityReader $units): array
    {
        $array = [];
        /** @var Unit $unit */
        foreach ($units as $unit) {
            $array[$unit->reqId()] = $unit->getUnitName() . ' ' . $unit->getUnitNamePlrl();
        }
        return $array;
    }

    /** @return array<int, string> */
    public static function unitPeppols(EntityReader $unit_peppols): array
    {
        $array = [];
        /** @var UnitPeppol $unit_peppol */
        foreach ($unit_peppols as $unit_peppol) {
            $array[$unit_peppol->reqId()] = $unit_peppol->getCode()
                . ' --- '
                . $unit_peppol->getName()
                . ' --- '
                . $unit_peppol->getDescription();
        }
        return $array;
    }

    /** @return array<int, string|null> */
    public static function taxRates(EntityReader $taxRates): array
    {
        $array = [];
        /** @var TaxRate $taxRate */
        foreach ($taxRates as $taxRate) {
            $array[$taxRate->reqId()] = $taxRate->getTaxRateName();
        }
        return $array;
    }

    /** @return array<string, string> */
    public static function optionsDataProducts(ProductRepository $pR): array
    {
        $options = [];
        /** @var \App\Infrastructure\Persistence\Product\Product $product */
        foreach ($pR->findAllPreloaded() as $product) {
            $sku = $product->getProductSku();
            if ($sku !== null && !in_array($sku, $options)) {
                $options[$sku] = $sku;
            }
        }
        return $options;
    }

    /** @return array<string, string> */
    public static function optionsDataFamilies(fR $fR): array
    {
        $options = [];
        /** @var Family $family */
        foreach ($fR->findAllPreloaded() as $family) {
            $familyId = $family->reqId();
            if ($familyId > 0 && !in_array($familyId, $options)) {
                $options[(string) $familyId] = (string) $family->getFamilyName();
            }
        }
        return $options;
    }
}
