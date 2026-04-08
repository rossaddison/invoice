<?php

declare(strict_types=1);

use App\Invoice\Entity\Inv;
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
 *      $parameters['invoice_draft_table']
 *      $parameters['invoice_sent_table']
 *      $parameters['invoice_viewed_table']
 *      $parameters['invoice_paid_table'] ... unpaid to written_off
 * @var App\Invoice\Helpers\ClientHelper $clientHelper
 * @var App\Invoice\Helpers\DateHelper $dateHelper
 * @var App\Invoice\InvAmount\InvAmountRepository $iaR
 * @var App\Invoice\Inv\InvRepository $iR
 * @var App\Invoice\InvRecurring\InvRecurringRepository $irR
 * @var App\Invoice\Setting\SettingRepository $s
 * @var App\Widget\GridComponents $gridComponents
 * @var App\Widget\PageSizeLimiter $pageSizeLimiter
 * @var Yiisoft\Data\Cycle\Reader\EntityReader $invoices
 * @var Yiisoft\Router\CurrentRoute $currentRoute
 * @var Yiisoft\Router\FastRoute\UrlGenerator $urlGenerator
 * @var Yiisoft\Session\SessionInterface $session
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var int $invoice_count
 */

$columns = [
 new DataColumn(
  header: $translator->translate('status'),
  content: static function (Inv $model) use ($iR, $iaR, $irR, $translator): string {
   $statusId = (string) $model->getStatusId();
   $invoiceId = (int) $model->getId();
   $inv_amount = $iaR->repoInvAmountCount($invoiceId) > 0 ?
           $iaR->repoInvquery($invoiceId) : null;
   $badge = Html::openTag('span', ['class' => 'badge text-bg-'
       . $iR->getSpecificStatusArrayClass((int) $statusId)])
    . Html::encode($iR->getSpecificStatusArrayLabel($statusId));
   if (!empty($invoiceId)
           && null !== $inv_amount
           && $iaR->repoInvAmountCount($invoiceId) > 0
           && $inv_amount->getSign() === -1) {
    $badge .= chr(160)
            . (string) Html::tag('i', '', ['class' => 'bi bi-receipt-cutoff',
                'title' => $translator->translate('credit.invoice')]);
   }
   if ($model->getIsReadOnly()) {
    $badge .= chr(160)
            . (string) Html::tag('i', '', ['class' => 'bi bi-lock',
                'title' => $translator->translate('read.only')]);
   }
   if ($irR->repoCount((string) $model->getId()) > 0) {
    $badge .= chr(160) . (string) Html::tag('i', '', [
        'class' => 'bi bi-arrow-repeat',
        'title' => $translator->translate('recurring')]);
   }
   $badge .= Html::closeTag('span');
   return $badge;
  },
  encodeContent: false,
  withSorting: false,
 ),
 new DataColumn(
  header: $translator->translate('invoice'),
  content: static function (Inv $model)
   use ($urlGenerator, $session, $translator): string {
   $args = ['_language' => (string) ($session->get('_language') ?? ''),
       'id' => $model->getId()];
   return (string) Html::a(
    Html::encode(null !== $model->getNumber()
            ? $model->getNumber()
            : (string) $model->getId()),
    $urlGenerator->generate('inv/view', $args),
    ['title' => $translator->translate('edit'), 'style' => 'text-decoration:none'],
   );
  },
  encodeContent: false,
  withSorting: false,
 ),
 new DataColumn(
  header: $translator->translate('created'),
  content: static fn (Inv $model): string =>
   $model->getDateCreated()->format('Y-m-d'),
  withSorting: false,
 ),
 new DataColumn(
  header: $translator->translate('due.date'),
  content: static function (Inv $model): string {
   return (string) Html::tag(
    'span',
    $model->getDateDue()->format('Y-m-d'),
    $model->isOverdue() ? ['class' => 'font-overdue'] : [],
   );
  },
  encodeContent: false,
  withSorting: false,
 ),
 new DataColumn(
  header: $translator->translate('client.name'),
  content: static function (Inv $model) use ($urlGenerator, $session,
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
  content: static function (Inv $model) use ($iaR, $s): string {
   $invoiceId = (int) $model->getId();
   $inv_amount = $iaR->repoInvAmountCount($invoiceId) > 0 ?
           $iaR->repoInvquery($invoiceId) : null;
   $class = (null !== $inv_amount && $inv_amount->getSign() === -1)
           ? 'amount text-danger' : 'amount';
   return (string) Html::span(
    $s->formatCurrency(null !== $inv_amount ? $inv_amount->getTotal() : 0.00),
    ['class' => $class, 'style' => 'float:right'],
   );
  },
  encodeContent: false,
  withSorting: false,
 ),
 new DataColumn(
  header: $translator->translate('balance'),
  content: static function (Inv $model) use ($iaR, $s): string {
   $invoiceId = (int) $model->getId();
   $inv_amount = $iaR->repoInvAmountCount($invoiceId) > 0 ?
           $iaR->repoInvquery($invoiceId) : null;
   return (string) Html::span(
    $s->formatCurrency(null !== $inv_amount ? $inv_amount->getBalance() : 0.00),
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
    url: static function (Inv $model) use ($urlGenerator, $session): string {
     if ($model->getIsReadOnly()) {
      return '';
     }
     return $urlGenerator->generate('inv/view', ['_language' =>
         (string) ($session->get('_language') ?? ''), 'id' => $model->getId()]);
    },
    attributes: static function (Inv $model) use ($translator): array {
     if ($model->getIsReadOnly()) {
      return ['class' => 'btn btn-secondary btn-sm disabled',
          'style' => 'pointer-events:none', 'aria-disabled' => 'true'];
     }
     return ['data-bs-toggle' => 'tooltip',
         'title' => $translator->translate('edit'),
         'class' => 'btn btn-outline-warning btn-sm'];
    },
   ),
   new ActionButton(
    content: (new I())->addClass('bi bi-printer'),
    url: static function (Inv $model) use ($urlGenerator): string {
     return $urlGenerator->generate('inv/pdfDashboardExcludeCf',
             ['id' => $model->getId()]);
    },
    attributes: ['data-bs-toggle' => 'tooltip',
        'title' => $translator->translate('download.pdf'),
        'class' => 'btn btn-outline-secondary btn-sm', 'target' => '_blank'],
   ),
   new ActionButton(
    content: (new I())->addClass('bi bi-send'),
    url: static function (Inv $model) use ($urlGenerator, $session): string {
     return $urlGenerator->generate('inv/emailStage0', [
         '_language' => (string) ($session->get('_language') ?? ''),
         'id' => $model->getId()]);
    },
    attributes: ['data-bs-toggle' => 'tooltip',
        'title' => $translator->translate('send.email'),
        'class' => 'btn btn-outline-info btn-sm'],
   ),
   new ActionButton(
    content: (new I())->addClass('bi bi-trash'),
    url: static function (Inv $model) use ($urlGenerator, $session, $s): string {
     $deletable = $model->getStatusId() === 1
      || ($s->getSetting('enable_invoice_deletion') == 1
             && $model->getIsReadOnly() !== true);
     if (!$deletable) {
      return '';
     }
     return $urlGenerator->generate('inv/delete',
             ['_language' => (string) ($session->get('_language') ?? ''),
                 'id' => $model->getId()]);
    },
    attributes: static function (Inv $model) use ($s, $translator): array {
     $deletable = $model->getStatusId() === 1
      || ($s->getSetting('enable_invoice_deletion') == 1
             && $model->getIsReadOnly() !== true);
     if (!$deletable) {
      return [
          'class' => 'btn btn-secondary btn-sm disabled',
          'style' => 'pointer-events:none', 'aria-disabled' => 'true'];
     }
     return [
      'data-bs-toggle' => 'tooltip',
      'title' => $translator->translate('delete'),
      'class' => 'btn btn-outline-danger btn-sm',
      'onclick' => 'return confirm('
         . (string) json_encode($translator->translate('delete.invoice.warning'))
         . ');',
     ];
    },
   ),
  ],
 ),
];

$paginator = (new OffsetPaginator($invoices))
 ->withPageSize(max(1, (int) $s->getSetting('default_list_limit')))
 ->withCurrentPage(1);

$gridSummary = $s->gridSummary(
 $paginator,
 $translator,
 (int) $s->getSetting('default_list_limit'),
 $translator->translate('invoices'),
 '',
);

echo GridView::widget()
 ->bodyRowAttributes(['class' => 'align-middle'])
 ->tableAttributes(['class' => 'table table-hover table-striped'])
 ->columns(...$columns)
 ->dataReader($paginator)
 ->id('w-inv-table-' . $invoice_count)
 ->noResultsCellAttributes(['class' => 'card-header bg-warning text-black'])
 ->noResultsText($translator->translate('no.records'))
 ->paginationWidget($gridComponents->offsetPaginationWidget($paginator))
 ->summaryAttributes(['class' => 'mt-3 me-3 summary d-flex justify-content-between align-items-center'])
 ->summaryTemplate('<div class="d-flex align-items-center">'
  . $pageSizeLimiter::buttons($currentRoute, $s, $translator, $urlGenerator, 'inv')
  . ' ' . $gridSummary . '</div>');
