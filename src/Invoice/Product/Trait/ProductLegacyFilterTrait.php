<?php

declare(strict_types=1);

namespace App\Invoice\Product\Trait;

use Yiisoft\Data\Cycle\Reader\EntityReader;

/**
 * Extracted from App\Invoice\Product\ProductRepository purely to stay under
 * SonarQube's S1448 (≤20 methods per class) — same reasoning as
 * ProductWebshopQueryTrait's own extraction, and the same
 * App\Invoice\Setting\Trait\Setting*Trait split SettingRepository already
 * uses. These four individual filterXxx() methods are superseded by
 * ProductRepository::filterCombined() (see project_inv_index_filter_combining_fix
 * memory) — kept in place non-destructively in case any other caller still
 * exercises them directly, not because product/index still relies on them.
 */
trait ProductLegacyFilterTrait
{
    public function filterFamilyId(int $family_id): EntityReader
    {
        $select = $this->select();
        $query = $select->where(['family_id' => $family_id]);
        return $this->prepareDataReader($query);
    }

    public function filterProductSku(string $product_sku): EntityReader
    {
        $select = $this->select();
        $query = $select->where(['product_sku' => ltrim(rtrim($product_sku))]);
        return $this->prepareDataReader($query);
    }

    public function filterProductPrice(string $product_price): EntityReader
    {
        $select = $this->select();
        $query = $select->where(['product_price' => ltrim(rtrim($product_price))]);
        return $this->prepareDataReader($query);
    }

    public function filterProductSkuPrice(string $product_price, string $product_sku): EntityReader
    {
        $select = $this->select();
        $query = $select->where(['product_price' => ltrim(rtrim($product_price))])
                        ->andWhere(['product_sku' => ltrim(rtrim($product_sku))]);
        return $this->prepareDataReader($query);
    }
}
