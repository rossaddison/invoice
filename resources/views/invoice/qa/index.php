<?php

declare(strict_types=1);

use App\Invoice\Entity\Qa;
use Yiisoft\Data\Paginator\OffsetPaginator;
use Yiisoft\Data\Paginator\PageToken;
use Yiisoft\Data\Reader\OrderHelper;
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
 * @var App\Invoice\Entity\Qa $qa
 * @var App\Invoice\Setting\SettingRepository $s
 * @var App\Widget\PageSizeLimiter $pageSizeLimiter
 * @var Yiisoft\Data\Paginator\OffsetPaginator $paginator
 * @var Yiisoft\Router\CurrentRoute $currentRoute
 * @var Yiisoft\Data\Cycle\Reader\EntityReader $qas
 * @var Yiisoft\Data\Paginator\OffsetPaginator $sortedAndPagedPaginator
 * @var Yiisoft\Router\FastRoute\UrlGenerator $urlGenerator
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var int $defaultPageSizeOffsetPaginator
 * @var string $alert
 * @var string $csrf
 * @var string $sortString
 * @psalm-var positive-int $page
 */

echo $s->getSetting('disable_flash_messages') == '0' ? $alert : '';

$toolbarReset =
    new A()
        ->addAttributes(['type' => 'reset'])
        ->addClass('btn btn-danger me-1 ajax-loader')
        ->content( new I()->addClass('bi bi-bootstrap-reboot'))
        ->href($urlGenerator->generate($currentRoute->getName() ?? 'qa/index'))
        ->id('btn-reset')
        ->render();

echo new Div();

    $columns = [
        new DataColumn(
            'id',
            header: $translator->translate('id'),
            content: static fn (Qa $model) => Html::encode($model->getId()),
            withSorting: true,
        ),
        new DataColumn(
            'active',
            header: $translator->translate('active'),
            content: static fn (Qa $model) => Html::encode($model->getActive() == '1' ?
                        ($translator->translate('active')
                            . ' '
                            . '✔️') : 
                        $translator->translate('inactive') . ' ' . '❌'),
        ),
        new DataColumn(
            'question',
            header: $translator->translate('faq.question'),
            content: static fn (Qa $model) => Html::encode($model->getQuestion()),
        ),
        new DataColumn(
            'answer',
            header: $translator->translate('faq.answer'),
            content: static fn (Qa $model) => Html::encode($model->getAnswer()),
        ),
        new ActionColumn(buttons: [
            new ActionButton(
                content: '🔎',
                url: function (Qa $model) use ($urlGenerator): string {
                    /** @psalm-suppress InvalidArgument */
                    return $urlGenerator->generate('qa/view', ['id' => $model->getId()]);
                },
                attributes: [
                    'data-bs-toggle' => 'tooltip',
                    'title' => $translator->translate('view'),
                ]
            ),
            new ActionButton(
                content: '✎',
                url: function (Qa $model) use ($urlGenerator): string {
                    /** @psalm-suppress InvalidArgument */
                    return $urlGenerator->generate('qa/edit', ['id' => $model->getId()]);
                },
                attributes: [
                    'data-bs-toggle' => 'tooltip',
                    'title' => $translator->translate('edit'),
                ]
            ),
            new ActionButton(
                content: '❌',
                url: function (Qa $model) use ($urlGenerator): string {
                    /** @psalm-suppress InvalidArgument */
                    return $urlGenerator->generate('qa/delete', ['id' => $model->getId()]);
                },
                attributes: [
                    'title' => $translator->translate('delete'),
                    'onclick' => "return confirm("
                        . "'"
                        . $translator->translate('delete.record.warning')
                        . "');"
                ]
            ),
        ]),
    ];

$urlCreator = new UrlCreator($urlGenerator);
$urlCreator->__invoke([], OrderHelper::stringToArray($sortString));
$sort = Sort::only(['id'])
        ->withOrderString($sortString);

$toolbarString =
    new Form()
        ->post($urlGenerator->generate('qa/index'))
        ->csrf($csrf)
        ->open() .
    new A()
        ->href($urlGenerator->generate('qa/add'))
        ->addStyle('text-decoration:none')
        ->content('➕')
        ->render() .
    new Div()
        ->addClass('float-end m-3')
        ->content($toolbarReset)
        ->encode(false)
        ->render() .
    new Form()
        ->close();

$sortedAndPagedPaginator = new OffsetPaginator($qas)
    ->withPageSize($defaultPageSizeOffsetPaginator > 0 ?
            $defaultPageSizeOffsetPaginator : 1)
    ->withCurrentPage($page)
    ->withSort($sort)
    ->withToken(PageToken::next((string) $page));

$gridSummary = $s->gridSummary(
    $sortedAndPagedPaginator,
    $translator,
    (int) $s->getSetting('default_list_limit'),
    $translator->translate('faq'),
    '',
);
    echo GridView::widget()
      ->bodyRowAttributes(['class' => 'align-middle'])
      ->tableAttributes([
          'class' => 'table table-striped text-center h-100',
          'id' => 'table-faq'])
      ->columns(...$columns)
      ->dataReader($sortedAndPagedPaginator)
      ->urlCreator($urlCreator)
      ->headerRowAttributes(['class' => 'card-header bg-info text-black'])
      ->header($translator->translate('faq'))
      ->multiSort(true)
      ->id('w1774234746-grid')
      ->summaryAttributes(['class' => 'mt-3 me-3 summary text-end'])
      ->summaryTemplate('<div class="d-flex align-items-center">'
        . $pageSizeLimiter::buttons(
            $currentRoute, $s, $translator, $urlGenerator, 'faq')
        . ' ' . $gridSummary . '</div>')
      ->noResultsCellAttributes(['class' => 'card-header bg-warning text-black'])
      ->noResultsText($translator->translate('no.records'))
      ->toolbar($toolbarString);
?>