<?php

declare(strict_types=1);

use App\Invoice\Quote\QuoteRepository as QR;
use App\Invoice\Quote\Widget\QuotesListWidget;
use App\Invoice\SalesOrder\SalesOrderRepository as SOR;
use App\Invoice\Setting\SettingRepository as SR;
use Yiisoft\Bootstrap5\Breadcrumbs;
use Yiisoft\Bootstrap5\BreadcrumbLink;
use Yiisoft\Data\Paginator\OffsetPaginator;
use Yiisoft\Html\Html;
use Yiisoft\Router\FastRoute\UrlGenerator;
use Yiisoft\Translator\TranslatorInterface;

/**
 * @var SR $s
 * @var QR $qR
 * @var SOR $soR
 * @var OffsetPaginator $paginator
 * @var UrlGenerator $urlGenerator
 * @var TranslatorInterface $translator
 * @var bool $visible
 * @var int $clientCount
 * @var int $decimalPlaces
 * @var string $alert
 * @var string $csrf
 * @var string|null $defaultQuoteGroup
 * @var string|null $groupBy
 * @var string $gridSummary
 * @var string $modal_add_quote
 * @var string $sortString
 * @psalm-var array<array-key, array<array-key, string>|string> $optionsDataClientsDropdownFilter
 * @psalm-var array<array-key, array<array-key, string>|string> $optionsDataStatusDropDownFilter
 */

echo $s->getSetting('disable_flash_messages') == '0' ? $alert : '';

$settingTabindex = 'setting/tabIndex';
echo Breadcrumbs::widget()
     ->links(
         BreadcrumbLink::to(
             label: $translator->translate('default.quote.group'),
             url: $urlGenerator->generate(
                 $settingTabindex,
                 [],
                 ['active' => 'quotes'],
                 'settings[default_quote_group]',
             ),
             active: true,
             attributes: [
                 'data-bs-toggle' => 'tooltip',
                 'title' => $defaultQuoteGroup ??
                 $translator->translate('not.set'),
             ],
             encodeLabel: false,
         ),
         BreadcrumbLink::to(
             label: $translator->translate('default.notes'),
             url: $urlGenerator->generate(
                 $settingTabindex,
                 [],
                 ['active' => 'quotes'],
                 'settings[default_quote_notes]',
             ),
             active: false,
             attributes: [
                 'data-bs-toggle' => 'tooltip',
                 'title' => $s->getSetting('default.quote.notes') ?:
                 $translator->translate('not.set'),
             ],
             encodeLabel: false,
         ),
         BreadcrumbLink::to(
             label: $translator->translate('quotes.expire.after'),
             url: $urlGenerator->generate(
                 $settingTabindex,
                 [],
                 ['active' => 'quotes'],
                 'settings[quotes_expire_after]',
             ),
             active: false,
             attributes: [
                 'data-bs-toggle' => 'tooltip',
                 'title' => $s->getSetting('quotes_expire_after') ?:
                 $translator->translate('not.set'),
             ],
             encodeLabel: false,
         ),
         BreadcrumbLink::to(
             label: $translator->translate('generate.quote.number.for.draft'),
             url: $urlGenerator->generate(
                 $settingTabindex,
                 [],
                 ['active' => 'quotes'],
                 'settings[generate_quote_number_for_draft]',
             ),
             active: false,
             attributes: [
                 'data-bs-toggle' => 'tooltip',
                 'title' => $s->getSetting('generate_quote_number_for_draft')
                 == '1' ? '✅' : '❌',
             ],
             encodeLabel: false,
         ),
         BreadcrumbLink::to(
             label: $translator->translate('default.email.template'),
             url: $urlGenerator->generate(
                 $settingTabindex,
                 [],
                 ['active' => 'quotes'],
                 'settings[email_quote_template]',
             ),
             active: false,
             attributes: [
                 'data-bs-toggle' => 'tooltip',
                 'title' => strlen($s->getSetting('email_quote_template')) > 0 ?
                    $s->getSetting('email_quote_template')
                    : $translator->translate('not.set'),
             ],
             encodeLabel: false,
         ),
         BreadcrumbLink::to(
             label: $translator->translate('pdf.quote.footer'),
             url: $urlGenerator->generate(
                 $settingTabindex,
                 [],
                 ['active' => 'quotes'],
                 'settings[pdf_quote_footer]',
             ),
             active: false,
             attributes: [
                 'data-bs-toggle' => 'tooltip',
                 'title' => $s->getSetting('pdf_quote_footer') ?:
                    $translator->translate('not.set'),
             ],
             encodeLabel: false,
         ),
     )
     ->listId(false)
     ->render();

echo QuotesListWidget::widget()
    ->withPaginator($paginator)
    ->withQR($qR)
    ->withSoR($soR)
    ->withSR($s)
    ->withCsrf($csrf)
    ->withDecimalPlaces($decimalPlaces)
    ->withVisible($visible)
    ->withGroupBy($groupBy ?? 'none')
    ->withClientCount($clientCount)
    ->withGridSummary($gridSummary)
    ->withSortString($sortString)
    ->withOptionsDataClientsDropdownFilter($optionsDataClientsDropdownFilter)
    ->withOptionsDataStatusDropDownFilter($optionsDataStatusDropDownFilter)
    ->render();

echo $modal_add_quote;
?>

<?php echo Html::script('InvoiceApp.initQuoteIndex()')->type('module'); ?>
