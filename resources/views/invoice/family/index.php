<?php

declare(strict_types=1);

use App\Invoice\Family\Widget\FamilyListWidget;
use App\Invoice\CategoryPrimary\CategoryPrimaryRepository;
use App\Invoice\CategorySecondary\CategorySecondaryRepository;
use App\Invoice\Setting\SettingRepository;
use Yiisoft\Data\Paginator\OffsetPaginator;

/**
 * @var SettingRepository $s
 * @var CategoryPrimaryRepository $cpR
 * @var CategorySecondaryRepository $csR
 * @var OffsetPaginator $paginator
 * @var string $alert
 * @var string $csrf
 * @var string $gridSummary
 * @var string $sortString
 * @var string $modal_generate_products
 */

echo $s->getSetting('disable_flash_messages') == '0' ? $alert : '';

echo FamilyListWidget::widget()
    ->withPaginator($paginator)
    ->withCpR($cpR)
    ->withCsR($csR)
    ->withCsrf($csrf)
    ->withGridSummary($gridSummary)
    ->withSortString($sortString)
    ->render();

echo $modal_generate_products;
