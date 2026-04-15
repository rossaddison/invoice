<?php

declare(strict_types=1);

use App\Invoice\Entity\Client;
use Yiisoft\Html\Html;
use Yiisoft\Html\Tag\A;
use Yiisoft\Html\Tag\Div;
use Yiisoft\Html\Tag\Form;
use Yiisoft\Html\Tag\Span;
use Yiisoft\Yii\DataView\GridView\GridView;
use Yiisoft\Yii\DataView\GridView\Column\DataColumn;

/**
 * A list of clients that the guest user has
 * @var App\Invoice\Entity\Client $client
 * @var App\Invoice\Entity\UserInv $userInv
 * @var App\Invoice\ClientPeppol\ClientPeppolRepository $cpR
 * @var App\Invoice\Inv\InvRepository $iR
 * @var App\Invoice\InvAmount\InvAmountRepository $iaR
 * @var App\Invoice\Setting\SettingRepository $s
 * @var App\Invoice\UserClient\UserClientRepository $ucR
 * @var App\Widget\Button $button
 * @var App\Widget\GridComponents $gridComponents
 * @var App\Widget\PageSizeLimiter $pageSizeLimiter
 * @var Yiisoft\Data\Paginator\OffsetPaginator $paginator
 * @var Yiisoft\Router\CurrentRoute $currentRoute
 * @var Yiisoft\Router\FastRoute\UrlGenerator $urlGenerator
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var Yiisoft\Yii\DataView\YiiRouter\UrlCreator $urlCreator
 * @var array $invoices
 * @var bool $editInv
 * @var int $defaultPageSizeOffsetPaginator
 * @var string $active
 * @var string $alert
 * @var string $csrf
 * @var string $modal_create_client
 */

echo $s->getSetting('disable_flash_messages') == '0' ? $alert : '';

$columns = [
    new DataColumn(
        'id',
        header: 'id',
        content: static fn (Client $model) => (string) $model->reqClientId(),
        withSorting: true,
    ),
    new DataColumn(
        'client_active',
        header: $translator->translate('active'),
        content: static function (Client $model) use ($button, $translator): Span {
            return $model->getClientActive() ? $button::activeLabel($translator) : $button::inactiveLabel($translator);
        },
        encodeContent: false,
    ),
    new DataColumn(
        'client_email',
        header: $translator->translate('email'),
        content: static function (Client $model): string {
            return $model->getClientEmail() ?: '';
        },
        encodeContent: true,
        withSorting: false,
    ),
    new DataColumn(
        'client_mobile',
        header: $translator->translate('mobile.number'),
        content: static function (Client $model): string {
            return $model->getClientMobile() ?? '';
        },
        encodeContent: true,
        withSorting: true,
    ),
    new DataColumn(
        property: 'client_name',
        header: $translator->translate('client.name'),
        content: static function (Client $model) use ($urlGenerator): A {
            return   new A()
                    ->content(Html::encode($model->getClientName()))
                    ->href($urlGenerator->generate('client/view', ['id' => $model->reqClientId()]))
                    ->addClass('btn btn-warning ms-2');
        },
        encodeContent: false,
        withSorting: false,
    ),
    new DataColumn(
        property: 'client_surname',
        header: $translator->translate('client.surname'),
        content: static function (Client $model) use ($urlGenerator): A {
            return   new A()
                    ->content(Html::encode($model->getClientSurname() ?? ''))
                    ->href($urlGenerator->generate('client/view', ['id' => $model->reqClientId()]))
                    ->addClass('btn btn-warning ms-2');
        },
        encodeContent: false,
        withSorting: false,
    ),
    new DataColumn(
        'client_phone',
        header: $translator->translate('phone'),
        content: static function (Client $model): string {
            return $model->getClientPhone() ?? '';
        },
        encodeContent: true,
        withSorting: true,
    ),
    new DataColumn(
        'invs',
        content: static function (Client $model) use ($iR, $iaR): int {
            $clientId = $model->reqClientId();
            $invoices = $iR->findAllWithClient($clientId);
            /**
             *  Initialize the ArrayCollection
             *  Related logic: see Doctrine\Common\Collections\ArrayCollection
             *  Related logic: see src\Invoice\Entity\Client function setInvs()
             */
            $model->setInvs();
            /**
             * @var App\Invoice\Entity\Inv $invoice
             */
            foreach ($invoices as $invoice) {
                $invoice_amount = ($iaR->repoInvAmountCount(
                        (int) $invoice->getId()) > 0 ?
                        $iaR->repoInvquery((int) $invoice->getId()) : null);
                if (null !== $invoice_amount
                        && null !== $invoice_amount->getBalance()
                        && $invoice_amount->getBalance() > 0) {
                    // Load the ArrayCollection
                    $model->addInv($invoice);
                }
            }
            /**
             * Use the ArrayCollection count method to determine how many
             * invoices there are for this client
             * Related logic: see \vendor\doctrine\Common\Collections\ArrayCollection count method;
             */
            return $model->getInvs()->count();
        },
        encodeContent: false,
    ),
    new DataColumn(
        'invs',
        content: static function (Client $model) use ($iR, $iaR,
        $urlGenerator, $gridComponents): string {
            $clientId = $model->reqClientId(); 
            $invoices = $iR->findAllWithClient($clientId);
            // Initialize a new empty ArrayCollection without the need to
            // create a new entity
            $model->setInvs();
            /**
             * @var App\Invoice\Entity\Inv $invoice
             */
            foreach ($invoices as $invoice) {
                $invoice_amount = ($iaR->repoInvAmountCount(
                        (int) $invoice->getId()) > 0 ?
                        $iaR->repoInvquery((int) $invoice->getId()) : null);
                if (null !== $invoice_amount
                        && null !== $invoice_amount->getBalance()
                        && $invoice_amount->getBalance() > 0) {
                    // Load into the ArrayCollection the invoices
                    // that make up this balance
                    $model->addInv($invoice);
                }
            }
            // Iterate across $model->getInvs()->toArray() to generate a mini
            // table with invoice number, invoice amount, and date
            return $gridComponents->gridMiniTableOfInvoicesForClient(
                $model,
                $min_invoices_per_row = 4,
                $urlGenerator,
            );
        },
        encodeContent: false,
    ),
    new DataColumn(
        'client_id',
        header: $translator->translate('balance')
            . ' ('
            . $s->getSetting('currency_symbol')
            . ')',
        content: static function (Client $model) use ($iR, $iaR, $s): string {
            $clientId = $model->reqClientId(); 
            return Html::encode($s->formatCurrency(
                $iR->withTotalBalance($clientId, $iaR)));
        },
    ),
];

$gridSummary = $s->gridSummary(
    $paginator,
    $translator,
    (int) $userInv->getListLimit(),
    $translator->translate('clients'),
    '',
);

$toolbarString
    =  new Form()
    ->post($urlGenerator->generate('client/index'))
    ->csrf($csrf)
    ->open()
    .  new Div()
        ->addClass('btn-group')
        ->content(
            $gridComponents->toolbarReset($urlGenerator)
            .  new A()
            ->href($urlGenerator->generate('client/index', ['page' => 1, 'active' => 2]))
            ->addClass('btn ' . ($active == 2 ? 'btn-primary' : 'btn-info'))
            ->content($translator->translate('all'))
            ->render()
            .  new A()
            ->href($urlGenerator->generate('client/index', ['page' => 1, 'active' => 1]))
            ->addClass('btn ' . ($active == 1 ? 'btn-primary' : 'btn-info'))
            ->content($translator->translate('active'))
            ->render()
            .  new A()
            ->href($urlGenerator->generate('client/index', ['page' => 1, 'active' => 0]))
            ->addClass('btn ' . ($active == 0 ? 'btn-primary' : 'btn-info'))
            ->content($translator->translate('inactive'))
            ->render(),
        )
        ->encode(false)->render()
    .  new Form()->close();

echo GridView::widget()
->bodyRowAttributes(['class' => 'align-middle'])
->tableAttributes(['class' => 'table table-striped text-center h-75','id' => 'table-client'])
->columns(...$columns)
->dataReader($paginator)
->urlCreator($urlCreator)
// the up and down symbol will appear at first indicating that the column can be sorted
// Ir also appears in this state if another column has been sorted
->sortableHeaderPrepend('<div class="float-end text-secondary text-opacity-50">⭥</div>')
// the up arrow will appear if column values are ascending
->sortableHeaderAscPrepend('<div class="float-end fw-bold">⭡</div>')
// the down arrow will appear if column values are descending
->sortableHeaderDescPrepend('<div class="float-end fw-bold">⭣</div>')
->headerRowAttributes(['class' => 'card-header bg-info text-black'])
->header($translator->translate('clients'))
->emptyCell($translator->translate('not.set'))
->emptyCellAttributes(['style' => 'color:red'])
->id('w34-grid')
->paginationWidget($gridComponents->offsetPaginationWidget($paginator))
->summaryAttributes(['class' => 'mt-3 me-3 summary text-end'])
->summaryTemplate($pageSizeLimiter::buttonsGuest($userInv, $urlGenerator, $translator, 'client', ($userInv->getListLimit() ?? 10)) . ' ' . $gridSummary)
->noResultsCellAttributes(['class' => 'card-header bg-warning text-black'])
->noResultsText($translator->translate('no.records'))
->toolbar($toolbarString);
