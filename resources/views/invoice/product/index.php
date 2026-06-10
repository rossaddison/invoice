<?php

declare(strict_types=1);

use App\Invoice\Product\Widget\ProductsListWidget;
use App\Invoice\ProductClient\ProductClientRepository as PcR;
use App\Invoice\Setting\SettingRepository as SR;
use Yiisoft\Data\Paginator\OffsetPaginator;
use Yiisoft\Router\FastRoute\UrlGenerator;
use Yiisoft\Translator\TranslatorInterface;

/**
 * @var SR $s
 * @var PcR $productClientR
 * @var OffsetPaginator $paginator
 * @var UrlGenerator $urlGenerator
 * @var TranslatorInterface $translator
 * @var bool $visible
 * @var string $alert
 * @var string $csrf
 * @var string $gridSummary
 * @var string $sortString
 * @psalm-var array<array-key, array<array-key, string>|string> $optionsDataProductsDropdownFilter
 * @psalm-var array<array-key, array<array-key, string>|string> $optionsDataFamiliesDropdownFilter
 */

echo $s->getSetting('disable_flash_messages') == '0' ? $alert : '';

echo ProductsListWidget::widget()
    ->withPaginator($paginator)
    ->withSR($s)
    ->withPcR($productClientR)
    ->withCsrf($csrf)
    ->withVisible($visible)
    ->withGridSummary($gridSummary)
    ->withSortString($sortString)
    ->withOptionsDataProductsDropdownFilter($optionsDataProductsDropdownFilter)
    ->withOptionsDataFamiliesDropdownFilter($optionsDataFamiliesDropdownFilter)
    ->render();
