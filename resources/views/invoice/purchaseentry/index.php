<?php

declare(strict_types=1);

use App\Infrastructure\Persistence\PurchaseEntry\PurchaseEntry;
use Yiisoft\Bootstrap5\Breadcrumbs;
use Yiisoft\Bootstrap5\BreadcrumbLink;
use Yiisoft\Data\Paginator\OffsetPaginator;
use Yiisoft\Data\Paginator\PageToken;
use Yiisoft\Data\Reader\Sort;
use Yiisoft\Html\Html;
use Yiisoft\Html\Tag\A;
use Yiisoft\Html\Tag\Div;
use Yiisoft\Html\Tag\Form;
use Yiisoft\Html\Tag\I;
use Yiisoft\Yii\DataView\GridView\Column\ActionButton;
use Yiisoft\Yii\DataView\GridView\Column\ActionColumn;
use Yiisoft\Yii\DataView\GridView\Column\DataColumn;
use Yiisoft\Yii\DataView\GridView\GridView;
use Yiisoft\Yii\DataView\YiiRouter\UrlCreator;

/**
 * @var App\Invoice\Setting\SettingRepository $s
 * @var App\Widget\GridComponents $gridComponents
 * @var Yiisoft\Data\Cycle\Reader\EntityReader $purchaseentries
 * @var Yiisoft\Router\CurrentRoute $currentRoute
 * @var Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var string $alert
 * @var string $csrf
 * @var string $groupBy
 * @var bool $taxYearSet
 * @var string $taxYearYear
 * @var string $taxYearMonth
 * @var string $taxYearDay
 * @psalm-var positive-int $page
 */

echo $s->getSetting('disable_flash_messages') == '0' ? $alert : '';

$settingTabIndex = 'setting/tabIndex';
$notSet          = '⏳';

echo Html::openTag('div', ['class' => 'd-flex align-items-center gap-3 mb-1']);
 echo Breadcrumbs::widget()
    ->links(
        BreadcrumbLink::to(
            label: 'Tax Year Start: Year',
            url: $urlGenerator->generate(
                $settingTabIndex,
                [],
                ['active' => 'taxes'],
                'settings[this_tax_year_from_date_year]',
            ),
            active: false,
            attributes: [
                'data-bs-toggle' => 'tooltip',
                'title' => $taxYearYear !== '' ? $taxYearYear : $notSet,
            ],
            encodeLabel: false,
        ),
        BreadcrumbLink::to(
            label: 'Tax Year Start: Month',
            url: $urlGenerator->generate(
                $settingTabIndex,
                [],
                ['active' => 'taxes'],
                'settings[this_tax_year_from_date_month]',
            ),
            active: false,
            attributes: [
                'data-bs-toggle' => 'tooltip',
                'title' => $taxYearMonth !== '' ? $taxYearMonth : $notSet,
            ],
            encodeLabel: false,
        ),
        BreadcrumbLink::to(
            label: 'Tax Year Start: Day',
            url: $urlGenerator->generate(
                $settingTabIndex,
                [],
                ['active' => 'taxes'],
                'settings[this_tax_year_from_date_day]',
            ),
            active: false,
            attributes: [
                'data-bs-toggle' => 'tooltip',
                'title' => $taxYearDay !== '' ? $taxYearDay : $notSet,
            ],
            encodeLabel: false,
        ),
    )
    ->listId(false)
    ->render();
 echo Html::a('📋 Locale defaults',
     $urlGenerator->generate('entry/tax-year-locales'),
     ['class' => 'btn btn-sm btn-outline-info text-nowrap mb-2', 'hx-boost' => 'false']);
echo Html::closeTag('div');

$sort = Sort::only(['id', 'date', 'supplier'])
    ->withOrder(['date' => 'desc']);

$paginator = (new OffsetPaginator($purchaseentries))
    ->withPageSize($s->positiveListLimit())
    ->withCurrentPage($page)
    ->withToken(PageToken::next((string) $page))
    ->withSort($sort);

$gridSummary = $s->gridSummary(
    $paginator,
    $translator,
    (int) $s->getSetting('default_list_limit'),
    'purchase entries',
    '',
);

$routeIndex = 'entry/index';
$btnSm      = 'btn btn-sm ';

$toolbarReset = new A()
    ->addAttributes(['type' => 'reset'])
    ->addClass('btn btn-danger me-1 ajax-loader')
    ->content(new I()->addClass('bi bi-bootstrap-reboot'))
    ->href($urlGenerator->generate($currentRoute->getName() ?? $routeIndex))
    ->id('btn-reset')
    ->render();

$groupNoneUrl     = $urlGenerator->generate($routeIndex, ['page' => 1, 'groupBy' => 'none']);
$groupMonthUrl    = $urlGenerator->generate($routeIndex, ['page' => 1, 'groupBy' => 'month']);
$groupSupplierUrl = $urlGenerator->generate($routeIndex, ['page' => 1, 'groupBy' => 'supplier']);

$quarterClass  = $btnSm . ($groupBy === 'quarter' ? 'btn-primary' : 'btn-outline-secondary');
$quarterAttrs  = ['class' => $quarterClass];
if (!$taxYearSet) {
    $quarterAttrs['class']        .= ' disabled';
    $quarterAttrs['aria-disabled'] = 'true';
    $quarterAttrs['tabindex']      = '-1';
}
$quarterHref   = $taxYearSet
    ? $urlGenerator->generate($routeIndex, ['page' => 1, 'groupBy' => 'quarter'])
    : '#';
$quarterButton = Html::a('By Quarter', $quarterHref, $quarterAttrs)->render();

$groupToggle = (new Div())->addClass('btn-group me-2')->encode(false)->content(
    Html::a('All', $groupNoneUrl, [
        'class' => $btnSm . ($groupBy === 'none' ? 'btn-primary' : 'btn-outline-primary'),
    ])->render()
    . Html::a('By Month', $groupMonthUrl, [
        'class' => $btnSm . ($groupBy === 'month' ? 'btn-primary' : 'btn-outline-primary'),
    ])->render()
    . Html::a('By Supplier', $groupSupplierUrl, [
        'class' => $btnSm . ($groupBy === 'supplier' ? 'btn-primary' : 'btn-outline-primary'),
    ])->render()
    . $quarterButton
)->render();

$toolbarString = new Form()
    ->post($urlGenerator->generate($routeIndex))
    ->csrf($csrf)
    ->open()
    . Html::a('➕ Add Entry', $urlGenerator->generate('entry/add'), ['class' => 'btn btn-info me-1', 'hx-boost' => 'false'])
    . Html::a('📥 CSV Import', $urlGenerator->generate('entry/csv-import'), ['class' => 'btn btn-outline-secondary me-1', 'hx-boost' => 'false'])
    . $groupToggle
    . (new Div())->addClass('float-end m-1')->content($toolbarReset)->encode(false)->render()
    . (new Form())->close();

// VAT quarter key: given a date and tax-year start month, returns e.g. "Q1 2025/2026".
$startMonth = (int) $taxYearMonth;
$vatQuarterKey = static function (DateTimeImmutable $date) use ($startMonth): string {
    $m       = (int) $date->format('n');
    $y       = (int) $date->format('Y');
    $quarter = (int) floor((($m - $startMonth + 12) % 12) / 3) + 1;
    $taxYear = $m < $startMonth ? $y - 1 : $y;
    return sprintf('Q%d %d/%d', $quarter, $taxYear, $taxYear + 1);
};

// HTMX wrapper: hx-boost + hx-select means pagination/group links swap only this section.
echo Html::openTag('div', [
    'id'          => 'purchase-entry-list',
    'hx-boost'    => 'true',
    'hx-target'   => '#purchase-entry-list',
    'hx-swap'     => 'outerHTML',
    'hx-select'   => '#purchase-entry-list',
]);

if ($groupBy === 'none') {

    $columns = [
        new DataColumn(
            'date',
            header: 'Date',
            content: static fn (PurchaseEntry $model): string =>
                Html::encode(($d = $model->getDate()) instanceof DateTimeImmutable ? $d->format('Y-m-d') : ''),
        ),
        new DataColumn(
            'supplier',
            header: 'Supplier',
            content: static fn (PurchaseEntry $model): string =>
                Html::encode($model->getSupplier()),
        ),
        new DataColumn(
            'description',
            header: 'Description',
            content: static fn (PurchaseEntry $model): string =>
                Html::encode((string) $model->getDescription()),
        ),
        new DataColumn(
            'amount_ex_vat',
            header: 'Amount ex-VAT',
            content: static fn (PurchaseEntry $model): string =>
                number_format($model->getAmountExVat(), 2),
        ),
        new DataColumn(
            'vat_amount',
            header: 'VAT Amount',
            content: static fn (PurchaseEntry $model): string =>
                number_format($model->getVatAmount(), 2),
        ),
        new ActionColumn(buttons: [
            new ActionButton(
                content: '✎',
                url: static fn (PurchaseEntry $model): string =>
                    $urlGenerator->generate('entry/edit', ['id' => $model->reqId()]),
                attributes: ['class' => 'btn btn-sm btn-outline-primary', 'data-bs-toggle' => 'tooltip', 'title' => 'Edit'],
            ),
            new ActionButton(
                content: '❌',
                url: static fn (PurchaseEntry $model): string =>
                    $urlGenerator->generate('entry/delete', ['id' => $model->reqId()]),
                attributes: [
                    'title'   => 'Delete',
                    'onclick' => "return confirm('Delete this entry?');",
                ],
            ),
        ]),
    ];

    echo GridView::widget()
        ->bodyRowAttributes(['class' => 'align-middle'])
        ->tableAttributes(['class' => 'table table-striped text-center h-75', 'id' => 'table-purchaseentry'])
        ->columns(...$columns)
        ->dataReader($paginator)
        ->urlCreator(new UrlCreator($urlGenerator))
        ->headerRowAttributes(['class' => 'card-header bg-info text-black'])
        ->header('Purchase Entries')
        ->id('w-purchaseentry-grid')
        ->paginationWidget($gridComponents->offsetPaginationWidget($paginator))
        ->summaryAttributes(['class' => 'mt-3 me-3 summary text-end'])
        ->summaryTemplate($gridSummary)
        ->noResultsCellAttributes(['class' => 'card-header bg-warning text-black'])
        ->noResultsText('No purchase entries yet.')
        ->toolbar($toolbarString);

} else {

    // Grouped view — fetch all entries (no pagination) and group in PHP.
    /** @var PurchaseEntry[] $all */
    $all = iterator_to_array($purchaseentries->withSort($sort)->read(), false);

    $groups = [];
    foreach ($all as $entry) {
        $date = $entry->getDate();
        $key  = match ($groupBy) {
            'month'    => $date instanceof DateTimeImmutable ? $date->format('Y-m') : 'Unknown',
            'quarter'  => $date instanceof DateTimeImmutable ? $vatQuarterKey($date) : 'Unknown',
            'supplier' => $entry->getSupplier() ?: 'Unknown',
            default    => 'All',
        };
        $groups[$key][] = $entry;
    }
    ksort($groups);

    echo Html::openTag('div', ['class' => 'card']);
     echo Html::openTag('div', ['class' => 'card-header d-flex justify-content-between align-items-center']);
      echo Html::tag('strong', 'Purchase Entries');
      echo Html::openTag('div', ['class' => 'd-flex gap-2 align-items-center']);
       echo Html::a('➕ Add Entry', $urlGenerator->generate('entry/add'), ['class' => 'btn btn-info btn-sm', 'hx-boost' => 'false']);
       echo Html::a('📥 CSV Import', $urlGenerator->generate('entry/csv-import'), ['class' => 'btn btn-outline-secondary btn-sm', 'hx-boost' => 'false']);
       echo $groupToggle;
      echo Html::closeTag('div');
     echo Html::closeTag('div');
     echo Html::openTag('div', ['class' => 'card-body p-0']);

    if (empty($groups)) {
        echo Html::tag('p', 'No purchase entries yet.', ['class' => 'text-muted text-center py-3']);
    }

    foreach ($groups as $groupLabel => $entries) {
        $subtotalEx  = array_sum(array_map(fn (PurchaseEntry $e) => $e->getAmountExVat(), $entries));
        $subtotalVat = array_sum(array_map(fn (PurchaseEntry $e) => $e->getVatAmount(), $entries));

        $groupHeading = match ($groupBy) {
            'month' => date('F Y', mktime(0, 0, 0, (int) substr($groupLabel, 5, 2), 1, (int) substr($groupLabel, 0, 4))),
            default => $groupLabel,
        };

        echo Html::openTag('table', ['class' => 'table table-sm table-bordered mb-0']);
         echo Html::openTag('thead', ['class' => 'table-secondary']);
          echo Html::openTag('tr');
           echo Html::tag('th', Html::encode($groupHeading), ['colspan' => '4']);
           echo Html::tag('th', number_format($subtotalEx, 2), ['class' => 'text-end']);
           echo Html::tag('th', number_format($subtotalVat, 2), ['class' => 'text-end']);
           echo Html::tag('th', '');
          echo Html::closeTag('tr');
          echo Html::openTag('tr', ['class' => 'table-light small']);
           echo Html::tag('th', 'Date');
           echo Html::tag('th', 'Supplier');
           echo Html::tag('th', 'Description', ['colspan' => '2']);
           echo Html::tag('th', 'Ex-VAT', ['class' => 'text-end']);
           echo Html::tag('th', 'VAT', ['class' => 'text-end']);
           echo Html::tag('th', '');
          echo Html::closeTag('tr');
         echo Html::closeTag('thead');
         echo Html::openTag('tbody');
         foreach ($entries as $entry) {
             $entryDate = $entry->getDate();
             echo Html::openTag('tr');
              echo Html::tag('td', Html::encode($entryDate instanceof DateTimeImmutable ? $entryDate->format('Y-m-d') : ''));
              echo Html::tag('td', Html::encode($entry->getSupplier()));
              echo Html::tag('td', Html::encode((string) $entry->getDescription()), ['colspan' => '2']);
              echo Html::tag('td', number_format($entry->getAmountExVat(), 2), ['class' => 'text-end']);
              echo Html::tag('td', number_format($entry->getVatAmount(), 2), ['class' => 'text-end']);
              echo Html::openTag('td', ['class' => 'd-flex gap-1']);
               echo Html::a('✎', $urlGenerator->generate('entry/edit', ['id' => $entry->reqId()]),
                   ['class' => 'btn btn-xs btn-outline-primary']);
               echo Html::a('❌', $urlGenerator->generate('entry/delete', ['id' => $entry->reqId()]),
                   ['class' => 'btn btn-xs btn-outline-danger',
                    'onclick' => "return confirm('Delete this entry?');"]);
              echo Html::closeTag('td');
             echo Html::closeTag('tr');
         }
         echo Html::closeTag('tbody');
        echo Html::closeTag('table');
    }

     echo Html::closeTag('div');
    echo Html::closeTag('div');
}

echo Html::closeTag('div'); // #purchase-entry-list
