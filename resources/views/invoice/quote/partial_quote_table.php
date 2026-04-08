<?php

declare(strict_types=1);

use App\Invoice\Entity\Quote;
use Yiisoft\Data\Cycle\Reader\EntityReader;
use Yiisoft\Data\Paginator\OffsetPaginator;
use Yiisoft\Html\Html;
use Yiisoft\Html\Tag\I;
use Yiisoft\Yii\DataView\GridView\Column\ActionButton;
use Yiisoft\Yii\DataView\GridView\Column\ActionColumn;
use Yiisoft\Yii\DataView\GridView\Column\DataColumn;
use Yiisoft\Yii\DataView\GridView\GridView;

/**
 * Related logic: see App\Invoice\Client\ClientController view function
 *      $parameters['quote_table']
 *      $parameters['quote_draft_table']
 *      $parameters['quote_sent_table']
 *      $parameters['quote_viewed_table']
 *      $parameters['quote_approved_table']
 *      $parameters['quote_rejected_table']
 *      $parameters['quote_cancelled_table']
 * @var App\Invoice\Helpers\ClientHelper $clientHelper
 * @var App\Invoice\Quote\QuoteRepository $qR
 * @var App\Invoice\QuoteAmount\QuoteAmountRepository $qaR
 * @var App\Invoice\Setting\SettingRepository $s
 * @var App\Widget\GridComponents $gridComponents
 * @var App\Widget\PageSizeLimiter $pageSizeLimiter
 * @var Yiisoft\Data\Cycle\Reader\EntityReader $quotes
 * @var Yiisoft\Router\CurrentRoute $currentRoute
 * @var Yiisoft\Router\FastRoute\UrlGenerator $urlGenerator
 * @var Yiisoft\Session\SessionInterface $session
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var int $quote_count
 */

$columns = [
 new DataColumn(
  header: $translator->translate('status'),
  content: static function (Quote $model) use ($qR): string {
   $statusId = (string) $model->getStatusId();
   return Html::openTag('span', ['class' => 'badge text-bg-'
       . $qR->getSpecificStatusArrayClass($statusId)])
    . Html::encode($qR->getSpecificStatusArrayLabel($statusId))
    . Html::closeTag('span');
  },
  encodeContent: false,
  withSorting: false,
 ),
 new DataColumn(
  header: $translator->translate('quote'),
  content: static function (Quote $model) use ($urlGenerator, $session,
          $translator): string {
   $args = ['_language' => (string) ($session->get('_language') ?? ''),
       'id' => $model->getId()];
   return (string) Html::a(
    Html::encode(null !== $model->getNumber()
            ? $model->getNumber()
            : (string) $model->getId()),
    $urlGenerator->generate('quote/view', $args),
    ['title' => $translator->translate('edit'), 'style' => 'text-decoration:none'],
   );
  },
  encodeContent: false,
  withSorting: false,
 ),
 new DataColumn(
  header: $translator->translate('created'),
  content: static fn (Quote $model): string =>
   $model->getDateCreated()->format('Y-m-d'),
  withSorting: false,
 ),
 new DataColumn(
  header: $translator->translate('due.date'),
  content: static fn (Quote $model): string =>
   $model->getDateExpires()->format('Y-m-d'),
  withSorting: false,
 ),
 new DataColumn(
  header: $translator->translate('client.name'),
  content: static function (Quote $model) use ($urlGenerator, $session,
          $clientHelper, $translator): string {
   $args = ['_language' => (string) ($session->get('_language') ?? ''),
       'id' => $model->getClientId()];
   return (string) Html::a(
    Html::encode($clientHelper->formatClient($model->getClient())),
    $urlGenerator->generate('client/view', $args),
    ['title' => $translator->translate('view.client'),
        'style' => 'text-decoration:none'],
   );
  },
  encodeContent: false,
  withSorting: false,
 ),
 new DataColumn(
  header: $translator->translate('amount'),
  content: static function (Quote $model) use ($qaR, $s): string {
   $quoteId = (string) $model->getId();
   $quote_amount = $qaR->repoQuoteAmountCount($quoteId) > 0
           ? $qaR->repoQuotequery($quoteId) : null;
   return (string) Html::span(
    $s->formatCurrency(null !== $quote_amount ? $quote_amount->getTotal() : 0.00),
    ['class' => 'amount', 'style' => 'float:right'],
   );
  },
  encodeContent: false,
  withSorting: false,
 ),
 new ActionColumn(
  before: Html::openTag('div', ['class' => 'btn-group', 'role' => 'group']),
  after: Html::closeTag('div'),
  buttons: [
   new ActionButton(
    content: (new I())->addClass('bi bi-pencil-square'),
    url: static function (Quote $model) use ($urlGenerator, $session): string {
     return $urlGenerator->generate('quote/view',
             ['_language' => (string) ($session->get('_language') ?? ''),
              'id' => $model->getId()]);
    },
    attributes: ['data-bs-toggle' => 'tooltip',
        'title' => $translator->translate('edit'),
        'class' => 'btn btn-outline-warning btn-sm'],
   ),
   new ActionButton(
    content: (new I())->addClass('bi bi-printer'),
    url: static function (Quote $model) use ($urlGenerator): string {
     return $urlGenerator->generate('quote/pdfDashboardExcludeCf',
             ['id' => $model->getId()]);
    },
    attributes: ['data-bs-toggle' => 'tooltip',
        'title' => $translator->translate('download.pdf'),
        'class' => 'btn btn-outline-secondary btn-sm', 'target' => '_blank'],
   ),
   new ActionButton(
    content: (new I())->addClass('bi bi-send'),
    url: static function (Quote $model) use ($urlGenerator, $session): string {
     return $urlGenerator->generate('quote/emailStage0',
             ['_language' => (string) ($session->get('_language') ?? ''),
              'id' => $model->getId()]);
    },
    attributes: ['data-bs-toggle' => 'tooltip',
        'title' => $translator->translate('send.email'),
        'class' => 'btn btn-outline-info btn-sm'],
   ),
   new ActionButton(
    content: (new I())->addClass('bi bi-trash'),
    url: static function (Quote $model) use ($urlGenerator, $session): string {
     $deletable = $model->getSoId() === '0' && $model->getInvId() === '0';
     if (!$deletable) {
      return '';
     }
     return $urlGenerator->generate('quote/delete',
             ['_language' => (string) ($session->get('_language') ?? ''),
              'id' => $model->getId()]);
    },
    attributes: static function (Quote $model) use ($translator): array {
     $deletable = $model->getSoId() === '0' && $model->getInvId() === '0';
     if (!$deletable) {
      return ['class' => 'btn btn-secondary btn-sm disabled',
          'style' => 'pointer-events:none', 'aria-disabled' => 'true'];
     }
     return [
      'data-bs-toggle' => 'tooltip',
      'title' => $translator->translate('delete'),
      'class' => 'btn btn-outline-danger btn-sm',
      'onclick' => 'return confirm('
         . (string) json_encode($translator->translate('delete.quote.warning'))
         . ');',
     ];
    },
   ),
  ],
 ),
];

$paginator = (new OffsetPaginator($quotes))
 ->withPageSize(max(1, (int) $s->getSetting('default_list_limit')))
 ->withCurrentPage(1);

$gridSummary = $s->gridSummary(
 $paginator,
 $translator,
 (int) $s->getSetting('default_list_limit'),
 $translator->translate('quotes'),
 '',
);

echo GridView::widget()
 ->bodyRowAttributes(['class' => 'align-middle'])
 ->tableAttributes(['class' => 'table table-hover table-striped'])
 ->columns(...$columns)
 ->dataReader($paginator)
 ->id('w-quote-table-' . $quote_count)
 ->noResultsCellAttributes(['class' => 'card-header bg-warning text-black'])
 ->noResultsText($translator->translate('no.records'))
 ->paginationWidget($gridComponents->offsetPaginationWidget($paginator))
 ->summaryAttributes(['class' => 'mt-3 me-3 summary d-flex justify-content-between align-items-center'])
 ->summaryTemplate('<div class="d-flex align-items-center">'
  . $pageSizeLimiter::buttons($currentRoute, $s, $translator, $urlGenerator, 'quote')
  . ' ' . $gridSummary . '</div>');
