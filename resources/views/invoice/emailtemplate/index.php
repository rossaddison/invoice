<?php

declare(strict_types=1);

use App\Invoice\Entity\EmailTemplate;
use Yiisoft\Html\Html;
use Yiisoft\Html\Tag\A;
use Yiisoft\Html\Tag\Div;
use Yiisoft\Html\Tag\Form;
use Yiisoft\Html\Tag\I;
use Yiisoft\Data\Paginator\OffsetPaginator;
use Yiisoft\Data\Paginator\PageToken;
use Yiisoft\Data\Reader\OrderHelper;
use Yiisoft\Data\Reader\Sort;
use Yiisoft\Yii\DataView\GridView\Column\ActionButton;
use Yiisoft\Yii\DataView\GridView\Column\ActionColumn;
use Yiisoft\Yii\DataView\GridView\Column\DataColumn;
use Yiisoft\Yii\DataView\GridView\GridView;
use Yiisoft\Yii\DataView\YiiRouter\UrlCreator;

/**
 * @var App\Invoice\Setting\SettingRepository $s
 * @var App\Widget\GridComponents $gridComponents
 * @var Yiisoft\Router\CurrentRoute $currentRoute
 * @var Yiisoft\Data\Cycle\Reader\EntityReader $email_templates
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var int $page
 * @var string $alert
 * @var string $csrf
 * @var string $sortString
 * @psalm-var positive-int $page
 */

echo $s->getSetting('disable_flash_messages') == '0' ? $alert : '';

$toolbarReset = new A()
    ->addAttributes(['type' => 'reset'])
    ->addClass('btn btn-danger me-1 ajax-loader')
    ->content(new I()->addClass('bi bi-bootstrap-reboot'))
    ->href($urlGenerator->generate($currentRoute->getName() ?? 'emailtemplate/index'))
    ->id('btn-reset')
    ->render();

$columns = [
    new DataColumn(
        property: 'id',
        header: $translator->translate('id'),
        content: static fn (EmailTemplate $model) => Html::encode((string) $model->getEmailTemplateId()),
        withSorting: true,
    ),
    new DataColumn(
        property: 'email_template_title',
        header: $translator->translate('title'),
        content: static fn (EmailTemplate $model) => Html::encode($model->getEmailTemplateTitle() ?? ''),
        withSorting: true,
    ),
    new DataColumn(
        property: 'email_template_type',
        header: $translator->translate('type'),
        content: static fn (EmailTemplate $model) => ucfirst($model->getEmailTemplateType() ?? 'invoice'),
    ),
    new DataColumn(
        property: 'preview',
        header: $translator->translate('preview'),
        content: static function (EmailTemplate $model) use ($urlGenerator): string {
            return (new A())
                ->href($urlGenerator->generate(
                    'emailtemplate/preview',
                    ['email_template_id' => $model->getEmailTemplateId()],
                ))
                ->content('🖼️')
                ->render();
        },
        encodeContent: false,
    ),
    new ActionColumn(buttons: [
        new ActionButton(
            content: '🔎',
            url: static function (EmailTemplate $model) use ($urlGenerator): string {
                return $urlGenerator->generate('emailtemplate/view',
                    ['email_template_id' => $model->getEmailTemplateId()]);
            },
            attributes: [
                'data-bs-toggle' => 'tooltip',
                'title' => $translator->translate('view'),
            ],
        ),
        new ActionButton(
            content: '✎',
            url: static function (EmailTemplate $model) use ($urlGenerator): string {
                return $urlGenerator->generate(
                    'emailtemplate/edit' . ($model->getEmailTemplateType() === 'Invoice' ? 'Invoice' : 'Quote'),
                    ['email_template_id' => $model->getEmailTemplateId()],
                );
            },
            attributes: [
                'data-bs-toggle' => 'tooltip',
                'title' => $translator->translate('edit'),
            ],
        ),
        new ActionButton(
            content: '❌',
            url: static function (EmailTemplate $model) use ($urlGenerator): string {
                return $urlGenerator->generate('emailtemplate/delete',
                    ['email_template_id' => $model->getEmailTemplateId()]);
            },
            attributes: [
                'title' => $translator->translate('delete'),
                'onclick' => "return confirm('"
                    . $translator->translate('delete.record.warning')
                    . "');",
            ],
        ),
    ]),
];

$urlCreator = new UrlCreator($urlGenerator);
$urlCreator->__invoke([], OrderHelper::stringToArray($sortString));
$sort = Sort::only(['id', 'email_template_title'])
    ->withOrderString($sortString);

$toolbarString = new Form()->post($urlGenerator->generate('emailtemplate/index'))->csrf($csrf)->open()
    . new A()
        ->href($urlGenerator->generate('emailtemplate/addInvoice'))
        ->addClass('btn btn-primary me-1')
        ->content('➕ ' . $translator->translate('invoice'))
        ->render()
    . new A()
        ->href($urlGenerator->generate('emailtemplate/addQuote'))
        ->addClass('btn btn-secondary me-1')
        ->content('➕ ' . $translator->translate('quote'))
        ->render()
    . new Div()->addClass('float-end m-3')->content($toolbarReset)->encode(false)->render()
    . new Form()->close();

$sortedAndPagedPaginator = (new OffsetPaginator($email_templates))
    ->withPageSize($s->positiveListLimit())
    ->withCurrentPage($page)
    ->withSort($sort)
    ->withToken(PageToken::next((string) $page));

$gridSummary = $s->gridSummary(
    $sortedAndPagedPaginator,
    $translator,
    (int) $s->getSetting('default_list_limit'),
    $translator->translate('email.templates'),
    '',
);

echo GridView::widget()
->bodyRowAttributes(['class' => 'align-middle'])
->tableAttributes(['class' => 'table table-striped text-center h-75'])
->columns(...$columns)
->dataReader($sortedAndPagedPaginator)
->urlCreator($urlCreator)
->headerRowAttributes(['class' => 'card-header bg-info text-black'])
->header($translator->translate('email.templates'))
->multiSort(true)
->id('w4-grid')
->paginationWidget($gridComponents->offsetPaginationWidget($sortedAndPagedPaginator))
->summaryAttributes(['class' => 'mt-3 me-3 summary text-end'])
->summaryTemplate($gridSummary)
->noResultsCellAttributes(['class' => 'card-header bg-warning text-black'])
->noResultsText($translator->translate('no.records'))
->toolbar($toolbarString);

