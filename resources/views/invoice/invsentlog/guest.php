<?php

declare(strict_types=1);

use App\Invoice\Entity\InvSentLog;
use Yiisoft\Html\Html;
use Yiisoft\Html\Tag\A;
use Yiisoft\Html\Tag\Div;
use Yiisoft\Html\Tag\Form;
use Yiisoft\Html\Tag\H5;
use Yiisoft\Html\Tag\I;
use Yiisoft\Yii\DataView\Column\DataColumn;
use Yiisoft\Yii\DataView\GridView;

/**
 * @var App\Invoice\Entity\UserInv $userInv
 * @var App\Invoice\Setting\SettingRepository $s
 * @var App\Widget\GridComponents $gridComponents
 * @var App\Widget\PageSizeLimiter $pageSizeLimiter
 * @var Yiisoft\Data\Paginator\OffsetPaginator $paginator
 * @var Yiisoft\Router\CurrentRoute $currentRoute
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var Yiisoft\Router\FastRoute\UrlGenerator $urlGenerator
 * @var bool $viewInv
 * @var int $defaultPageSizeOffsetPaginator
 * @var string $alert
 * @var string $csrf
 * @psalm-var array<array-key, array<array-key, string>|string> $optionsDataGuestInvNumberDropDownFilter
 */

echo $alert;

/*
 * Related logic: see https://emojipedia.org/incoming-envelope
 */
?>

<h1>📨</h1>

<?php
    $header = Div::tag()
        ->addClass('row')
        ->content(
            H5::tag()
            ->addClass('bg-primary text-white p-3 rounded-top')
            ->content(
                I::tag()->content('📨'),
            ),
        )
        ->render();

$toolbarReset = A::tag()
  ->addAttributes(['type' => 'reset'])
  ->addClass('btn btn-danger me-1 ajax-loader')
  ->content(I::tag()->addClass('bi bi-bootstrap-reboot'))
  ->href($urlGenerator->generate($currentRoute->getName() ?? 'invsentlog/guest'))
  ->id('btn-reset')
  ->render();

$toolbar = Div::tag();

$columns = [
    new DataColumn(
        'id',
        header: $translator->translate('id'),
        content: static function (InvSentLog $model): string {
            return (string) $model->getId();
        },
    ),
    new DataColumn(
        property: 'filterInvNumber',
        header: $translator->translate('number'),
        content: static function (InvSentLog $model) use ($urlGenerator): A {
            return Html::a(($model->getInv()?->getNumber() ?? '#') . ' 🔍', $urlGenerator->generate(
                'inv/view',
                ['id' => $model->getId()],
            ), ['style' => 'text-decoration:none']);
        },
        filter: $optionsDataGuestInvNumberDropDownFilter,
        withSorting: false,
    ),
    new DataColumn(
        'inv_id',
        header: $translator->translate('setup.db.username.info'),
        content: static fn(InvSentLog $model) => $model->getInv()?->getUser()->getLogin(),
    ),
    new DataColumn(
        'client_id',
        header: $translator->translate('client'),
        content: static fn(InvSentLog $model): string => Html::encode($model->getClient()?->getClient_full_name() ?? ''),
    ),
    new DataColumn(
        'date_sent',
        header: $translator->translate('email.date'),
        content: static fn(InvSentLog $model): string => ($model->getDate_sent())->format('l, d-M-Y H:i:s T'),
    ),
];

echo '<br>';
$grid_summary = $s->grid_summary(
    $paginator,
    $translator,
    $defaultPageSizeOffsetPaginator,
    $translator->translate('email.logs'),
    '',
);
$toolbarString = Form::tag()->post($urlGenerator->generate('invsentlog/guest'))->csrf($csrf)->open() .
                 Div::tag()->addClass('float-end m-3')->content($toolbarReset)->encode(false)->render() .
                 Form::tag()->close();
echo GridView::widget()
  ->bodyRowAttributes(['class' => 'align-middle'])
  ->tableAttributes(['class' => 'table table-striped text-center h-10463', 'id' => 'table-invsentlog'])
  ->columns(...$columns)
  ->dataReader($paginator)
  ->headerRowAttributes(['class' => 'card-header bg-info text-black'])
  ->header($header)
  ->id('w10463-grid')
  ->paginationWidget($gridComponents->offsetPaginationWidget($paginator))
  ->summaryAttributes(['class' => 'mt-3 me-3 summary text-end'])
  ->summaryTemplate(($viewInv ?
                       $pageSizeLimiter::buttonsGuest($userInv, $urlGenerator, $translator, 'invsentlog', $defaultPageSizeOffsetPaginator) : '') . ' ' .
                       $grid_summary)->emptyTextAttributes(['class' => 'card-header bg-warning text-black'])
  ->emptyText($translator->translate('no.records'))
  ->toolbar($toolbarString);
