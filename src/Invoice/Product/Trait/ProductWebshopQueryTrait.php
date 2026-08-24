<?php

declare(strict_types=1);

namespace App\Invoice\Product\Trait;

use Yiisoft\Data\Cycle\Reader\EntityReader;

/**
 * Extracted from App\Invoice\Product\ProductRepository purely to stay
 * under SonarQube's S1448 (≤20 methods per class) — same reasoning as
 * App\Invoice\Product\ProductAttachmentController's own extraction from
 * ProductController, and the same App\Invoice\Setting\Trait\Setting*Trait
 * split SettingRepository already uses.
 */
trait ProductWebshopQueryTrait
{
    /**
     * Same as findAllPreloadedWithPrice(), plus available_on_webshop —
     * the staff invoice/quote product picker (findAllPreloadedWithPrice()
     * itself) deliberately still shows every priced product regardless
     * of this flag; only the public /shop catalog
     * (App\Webshop\Catalog\CatalogQueryService::listAll()) needs the
     * extra condition, so it gets its own method rather than a new
     * parameter on the shared one.
     *
     * @psalm-return EntityReader
     */
    public function findAllPreloadedWithPriceAvailableOnWebshop(): EntityReader
    {
        $query = $this->select()
            ->load('family')
            ->load('tax_rate')
            ->load('unit')
            ->where(['product_price' => ['>' => 0]])
            ->andWhere(['available_on_webshop' => true]);
        return $this->prepareDataReader($query);
    }
}
