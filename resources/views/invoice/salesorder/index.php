<?php

declare(strict_types=1);

use App\Invoice\SalesOrder\SalesOrderRepository as SoR;
use App\Invoice\SalesOrder\Widget\SalesOrdersListWidget;
use App\Invoice\SalesOrderAmount\SalesOrderAmountRepository as SoAR;
use App\Invoice\Setting\SettingRepository as SR;
use Yiisoft\Bootstrap5\Breadcrumbs;
use Yiisoft\Bootstrap5\BreadcrumbLink;
use Yiisoft\Data\Paginator\OffsetPaginator;
use Yiisoft\Router\FastRoute\UrlGenerator;
use Yiisoft\Translator\TranslatorInterface;

/**
 * @var SR $s
 * @var SoR $soR
 * @var SoAR $soaR
 * @var App\Invoice\Inv\InvRepository $iR
 * @var OffsetPaginator $paginator
 * @var UrlGenerator $urlGenerator
 * @var TranslatorInterface $translator
 * @var bool $visible
 * @var int $status
 * @var string $alert
 * @var string $csrf
 * @var string $gridSummary
 * @var string $groupBy
 * @var string $salesOrderToolbar
 * @var string $sortString
 * @psalm-var array<array-key, array<array-key, string>|string> $optionsDataClientsDropdownFilter
 */

echo $s->getSetting('disable_flash_messages') == '0' ? $alert : '';

$settingTabIndex = 'setting/tabIndex';
echo Breadcrumbs::widget()
    ->links(
        BreadcrumbLink::to(
            label: $translator->translate('salesorder'),
            url: $urlGenerator->generate('salesorder/index'),
            active: true,
            encodeLabel: false,
        ),
        BreadcrumbLink::to(
            label: $translator->translate('setting'),
            url: $urlGenerator->generate(
                $settingTabIndex,
                [],
                ['active' => 'salesorders'],
            ),
            active: false,
            encodeLabel: false,
        ),
    )
    ->listId(false)
    ->render();

echo SalesOrdersListWidget::widget()
    ->withPaginator($paginator)
    ->withSoR($soR)
    ->withSoAR($soaR)
    ->withIR($iR)
    ->withSR($s)
    ->withCsrf($csrf)
    ->withVisible($visible)
    ->withGroupBy($groupBy)
    ->withGridSummary($gridSummary)
    ->withSortString($sortString)
    ->withStatus($status)
    ->withSalesOrderToolbar($salesOrderToolbar)
    ->withOptionsDataClientsDropdownFilter($optionsDataClientsDropdownFilter)
    ->render();
