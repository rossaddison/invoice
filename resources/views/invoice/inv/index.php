<?php

declare(strict_types=1);

use App\Invoice\Asset\YiiDataViewNoInlineJsAsset;
use App\Invoice\Inv\Widget\InvsFilterOptions;
use App\Invoice\Inv\Widget\InvsListWidget;
use Yiisoft\Bootstrap5\Breadcrumbs;
use Yiisoft\Bootstrap5\BreadcrumbLink;
use Yiisoft\Html\Html as H;

/**
 * @var App\Invoice\CategorySecondary\CategorySecondaryRepository $csR
 * @var App\Invoice\DeliveryLocation\DeliveryLocationRepository $dlR
 * @var App\Invoice\Dwelling\DwellingRepository $dwR
 * @var App\Invoice\Inv\InvRepository $iR
 * @var App\Invoice\InvRecurring\InvRecurringRepository $irR
 * @var App\Invoice\InvSentLog\InvSentLogRepository $islR
 * @var App\Invoice\Quote\QuoteRepository $qR
 * @var App\Invoice\SalesOrder\SalesOrderRepository $soR
 * @var App\Invoice\Setting\SettingRepository $s
 * @var App\Invoice\EmailTemplate\EmailTemplateRepository $etR
 * @var App\Invoice\FromDropDown\FromDropDownRepository $fdR
 * @var App\Invoice\Worker\WorkerRepository $wR
 * @var Yiisoft\Data\Paginator\OffsetPaginator $paginator
 * @var Yiisoft\Router\FastRoute\UrlGenerator $urlGenerator
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var bool $visible
 * @var bool $visibleToggleInvSentLogColumn
 * @var int $clientCount
 * @var int $decimalPlaces
 * @var string $alert
 * @var string $csrf
 * @var string|null $defaultInvoiceGroup
 * @var string|null $defaultInvoicePaymentMethod
 * @var string $gridSummary
 * @var string|null $groupBy

 * @var string $modal_add_inv
 * @var string $modal_copy_inv_multiple
 * @var string $modal_create_recurring_multiple
 * @var string $sortString
 * @psalm-var array<array-key, array<array-key, string>|string> $optionsInvNumberDropDownFilter
 * @psalm-var array<array-key, array<array-key, string>|string> $optionsCreditInvNumberDropDownFilter
 * @psalm-var array<array-key, array<array-key, string>|string> $optionsFamilyNameDropDownFilter
 * @psalm-var array<array-key, array<array-key, string>|string> $optionsClientsDropDownFilter
 * @psalm-var array<array-key, array<array-key, string>|string> $optionsClientGroupDropDownFilter
 * @psalm-var array<array-key, array<array-key, string>|string> $optionsYearMonthDropDownFilter
 * @psalm-var array<array-key, array<array-key, string>|string> $optionsStatusDropDownFilter
 * @psalm-var array<array-key, array<array-key, string>|string> $optionsCategorySecondaryRunDropDownFilter
 * @var Yiisoft\Assets\AssetManager $assetManager
 */

$settingTabIndex = 'setting/tabIndex';

// The client filter column below is the one DropdownFilter in this app
// using useInlineJs(false) (InvsColumnBuilder::buildColumns()) -- see
// project_yii_dataview_csp_upstream_resolution memory. Registered here
// rather than globally in the layout since every other filter in this
// app still uses its own equivalent (native-reset + data-actions.ts),
// which doesn't need this file at all.
$assetManager->register(YiiDataViewNoInlineJsAsset::class);

echo $s->getSetting('disable_flash_messages') == '0' ? $alert : '';

echo Breadcrumbs::widget()
 ->links(
     BreadcrumbLink::to(
         label: $translator->translate('default.invoice.group'),
         url: $urlGenerator->generate(
             $settingTabIndex,
             [],
             ['active' => 'invoices'],
             'settings[default_invoice_group]',
         ),
         active: true,
         attributes: [
             'data-bs-toggle' => 'tooltip',
             'title' => $defaultInvoiceGroup ?? $translator->translate('not.set'),
         ],
         encodeLabel: false,
     ),
     BreadcrumbLink::to(
         label: $translator->translate('default.terms'),
         url: $urlGenerator->generate(
             $settingTabIndex,
             [],
             ['active' => 'invoices'],
             'settings[default_invoice_terms]',
         ),
         active: false,
         attributes: [
             'data-bs-toggle' => 'tooltip',
             'title' => $s->getSetting('default_invoice_terms')
             ?: $translator->translate('not.set'),
         ],
         encodeLabel: false,
     ),
     BreadcrumbLink::to(
         label: $translator->translate('default.payment.method'),
         url: $urlGenerator->generate(
             $settingTabIndex,
             [],
             ['active' => 'invoices'],
             'settings[invoice_default_payment_method]',
         ),
         active: false,
         attributes: [
             'data-bs-toggle' => 'tooltip',
             'title' => $defaultInvoicePaymentMethod
             ?? $translator->translate('not.set'),
         ],
         encodeLabel: false,
     ),
     BreadcrumbLink::to(
         label: $translator->translate('invoices.due.after'),
         url: $urlGenerator->generate(
             $settingTabIndex,
             [],
             ['active' => 'invoices'],
             'settings[invoices_due_after]',
         ),
         active: false,
         attributes: [
             'data-bs-toggle' => 'tooltip',
             'title' => $s->getSetting('invoices_due_after')
             ?: $translator->translate('not.set'),
         ],
         encodeLabel: false,
     ),
     BreadcrumbLink::to(
         label: $translator->translate('generate.invoice.number.for.draft'),
         url: $urlGenerator->generate(
             $settingTabIndex,
             [],
             ['active' => 'invoices'],
             'settings[generate_invoice_number_for_draft]',
         ),
         active: false,
         attributes: [
             'data-bs-toggle' => 'tooltip',
             'title' => $s->getSetting('generate_invoice_number_for_draft')
                == '1' ? '✅' : '❌',
         ],
         encodeLabel: false,
     ),
     BreadcrumbLink::to(
         label: $translator->translate('homecare.auto.invoice.enabled'),
         url: $urlGenerator->generate(
             $settingTabIndex,
             [],
             ['active' => 'invoices'],
             'settings[homecare_auto_invoice_enabled]',
         ),
         active: false,
         attributes: [
             'data-bs-toggle' => 'tooltip',
             'title' => $s->getSetting('homecare_auto_invoice_enabled')
                == '1' ? '✅' : '❌',
         ],
         encodeLabel: false,
     ),
     BreadcrumbLink::to(
         label: $translator->translate('recurring'),
         url: $urlGenerator->generate('invrecurring/index'),
     ),
     BreadcrumbLink::to(
         label: $translator->translate('set.to.read.only')
             . ' '
             . $iR->getSpecificStatusArrayEmoji(
                (int) $s->getSetting('read_only_toggle')),
         url: $urlGenerator->generate(
             $settingTabIndex,
             [],
             ['active' => 'invoices'],
             'settings[read_only_toggle]',
         ),
     ),
 )
 ->listId(false)
 ->render();

echo InvsListWidget::widget()
    ->withPaginator($paginator)
    ->withIR($iR)
    ->withLogRepositories($irR, $islR)
    ->withRelationRepositories($qR, $soR, $dlR)
    ->withSR($s)
    ->withEmailRepositories($etR, $fdR)
    ->withWorkerRepository($wR)
    ->withCategorySecondaryRepository($csR)
    ->withDwellingRepository($dwR)
    ->withCsrf($csrf)
    ->withDecimalPlaces($decimalPlaces)
    ->withVisible($visible)
    ->withVisibleInvSentLogColumn($visibleToggleInvSentLogColumn)
    ->withGroupBy($groupBy ?? 'none')
    ->withClientCount($clientCount)
    ->withGridDisplayOptions($gridSummary, $sortString)
    ->withFilterOptions(new InvsFilterOptions([
        'invNumber'       => $optionsInvNumberDropDownFilter,
        'creditInvNumber' => $optionsCreditInvNumberDropDownFilter,
        'familyName'      => $optionsFamilyNameDropDownFilter,
        'clients'         => $optionsClientsDropDownFilter,
        'clientGroup'     => $optionsClientGroupDropDownFilter,
        'yearMonth'       => $optionsYearMonthDropDownFilter,
        'status'          => $optionsStatusDropDownFilter,
        'categorySecondaryRun' => $optionsCategorySecondaryRunDropDownFilter,
    ]))
    ->render();

echo $modal_add_inv;
echo $modal_create_recurring_multiple;
echo $modal_copy_inv_multiple;


// Angular Amount Magnifier Integration

echo H::openTag('div', ['id' => 'angular-amount-magnifier-app']);
 echo H::openTag('app-root');
 echo H::closeTag('app-root');
echo H::closeTag('div');

$filterPromptLabels = json_encode([
    'filter-inv-number'        => '— ' . $translator->translate('number') . ' —',
    'filter-credit-inv-number' => '— ' . $translator->translate(
        'credit.invoice.for.invoice') . ' —',
    'filter-family-name'       => '— ' . $translator->translate('family.name') .
     ' —',
    'filter-year-month'   => '— ' . $translator->translate(
        'datetime.immutable.date.created.mySql.format.year.month.filter')  . ' —',
    'filter-status'       => '— ' . $translator->translate('status') . ' —',
    'filter-client'       => '— ' . $translator->translate('client') . ' —',
    'filter-client-group' => '— ' . $translator->translate('client.group') . ' —',
], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_THROW_ON_ERROR);
echo H::tag('script', $filterPromptLabels, ['type' => 'application/json',
    'id' => 'inv-filter-config']);
// InvoiceApp.initInvIndex() now self-invokes from index.ts when #table-invoice
// is present, so script-src no longer needs 'unsafe-inline' for this call.
