<?php

declare(strict_types=1);

use App\Invoice\Entity\Gentor;
use Yiisoft\Html\Html;
use Yiisoft\Html\Tag\A;
use Yiisoft\Html\Tag\Div;
use Yiisoft\Html\Tag\Form;
use Yiisoft\Html\Tag\I;
use Yiisoft\Yii\DataView\GridView\Column\ActionButton;
use Yiisoft\Yii\DataView\GridView\Column\ActionColumn;
use Yiisoft\Yii\DataView\GridView\Column\DataColumn;
use Yiisoft\Yii\DataView\GridView\GridView;

/**
 * @var App\Invoice\GeneratorRelation\GeneratorRelationRepository $grR
 * @var App\Invoice\Setting\SettingRepository $s
 * @var App\Widget\GridComponents $gridComponents
 * @var App\Widget\PageSizeLimiter $pageSizeLimiter
 * @var Yiisoft\Router\CurrentRoute $currentRoute
 * @var Yiisoft\Data\Paginator\OffsetPaginator $paginator
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var Yiisoft\Router\FastRoute\UrlGenerator $urlFastRouteGenerator
 * @var int $defaultPageSizeOffsetPaginator
 * @var string $alert
 * @var string $csrf
 */

echo $s->getSetting('disable_flash_messages') == '0' ? $alert : '';

$toolbarReset =  new A()
    ->addAttributes(['type' => 'reset'])
    ->addClass('btn btn-danger me-1 ajax-loader')
    ->content( new I()->addClass('bi bi-bootstrap-reboot'))
    ->href($urlGenerator->generate($currentRoute->getName() ?? 'generator/index'))
    ->id('btn-reset')
    ->render();

$columns = [
    new DataColumn(
        'id',
        header: $translator->translate('id'),
        content: static fn (Gentor $model) => Html::encode($model->getGentorId() . '➡️' . $model->getCamelcaseCapitalName()),
        encodeContent: false,
    ),
    new DataColumn(
        'id',
        header: $translator->translate('generator.relations'),
        content: static function (Gentor $model) use ($urlGenerator, $grR): string {
            $div_open_tag = Html::openTag('div', ['class' => 'btn-group']);

            $entity_name_render = Html::a(
                Html::encode($model->getCamelcaseCapitalName()),
                $urlGenerator->generate(
                    'generator/view',
                    ['id' => $model->getGentorId()],
                ),
                ['class' => 'btn btn-primary btn-sm active','aria-current' => 'page'],
            )->render();

            $relations = $grR->repoGeneratorquery($model->getGentorId());
            $relations_content_render = '';
            /**
             * @var App\Invoice\Entity\GentorRelation $relation
             */
            foreach ($relations as $relation) {
                $relations_content_render .= Html::a(
                    $relation->getLowercaseName() ?? '#',
                    $urlGenerator->generate(
                        'generatorrelation/edit',
                        ['id' => $relation->getRelationId()],
                    ),
                    ['class' => 'btn btn-primary btn-sm'],
                )->render();
            }

            //modal delete button
            $div_close_tag = Html::closeTag('div');

            return

            $div_open_tag
                . $entity_name_render
                . $relations_content_render
            . $div_close_tag;
        },
        encodeContent: false,
    ),
    new ActionColumn(buttons: [
        new ActionButton(
            content: '🔎',
            url: static function (Gentor $model) use ($urlGenerator): string {
                return $urlGenerator->generate('generator/view', ['id' => $model->getGentorId()]);
            },
            attributes: [
                'data-bs-toggle' => 'tooltip',
                'title' => $translator->translate('view'),
            ],
        ),
        new ActionButton(
            content: '✎',
            url: static function (Gentor $model) use ($urlGenerator): string {
                return $urlGenerator->generate('generator/edit', ['id' => $model->getGentorId()]);
            },
            attributes: [
                'data-bs-toggle' => 'tooltip',
                'title' => $translator->translate('edit'),
            ],
        ),
        new ActionButton(
            content: '❌',
            url: static function (Gentor $model) use ($urlGenerator): string {
                return $urlGenerator->generate('generator/delete', ['id' => $model->getGentorId()]);
            },
            attributes: [
                'title' => $translator->translate('delete'),
                'onclick' => "return confirm(" . "'" . $translator->translate('delete.record.warning') . "');",
            ],
        ),
    ]),
    new DataColumn(
        'id',
        header : 'Entity',
        content: static function (Gentor $model) use ($urlGenerator): A {
            return

            Html::a(
                'Entity' . DIRECTORY_SEPARATOR . $model->getCamelcaseCapitalName(),
                $urlGenerator->generate('generator/entity', ['id' => $model->getGentorId()]),
                ['class' => 'btn btn-secondary btn-sm ms-2'],
            );
        },
        encodeContent: false,
    ),
    new DataColumn(
        'id',
        header: 'Controller',
        content: static function (Gentor $model) use ($urlGenerator): A {
            return

            Html::a(
                $model->getCamelcaseCapitalName() . 'Controller',
                $urlGenerator->generate('generator/controller', ['id' => $model->getGentorId()]),
                ['class' => 'btn btn-secondary btn-sm ms-2'],
            );
        },
        encodeContent: false,
    ),
    new DataColumn(
        'id',
        header: 'Form',
        content: static function (Gentor $model) use ($urlGenerator): A {
            return

            Html::a(
                $model->getCamelcaseCapitalName() . 'Form',
                $urlGenerator->generate('generator/form', ['id' => $model->getGentorId()]),
                ['class' => 'btn btn-secondary btn-sm ms-2'],
            );
        },
        encodeContent: false,
    ),
    new DataColumn(
        'id',
        header: 'Repository',
        content: static function (Gentor $model) use ($urlGenerator): A {
            return

            Html::a(
                $model->getCamelcaseCapitalName() . 'Repository',
                $urlGenerator->generate('generator/repo', ['id' => $model->getGentorId()]),
                ['class' => 'btn btn-secondary btn-sm ms-2'],
            );
        },
        encodeContent: false,
    ),
    new DataColumn(
        'id',
        header: 'Service',
        content: static function (Gentor $model) use ($urlGenerator): A {
            return
            Html::a(
                $model->getCamelcaseCapitalName() . 'Service',
                $urlGenerator->generate('generator/service', ['id' => $model->getGentorId()]),
                ['class' => 'btn btn-secondary btn-sm ms-2'],
            );
        },
        encodeContent: false,
    ),
    new DataColumn(
        'id',
        header: 'index',
        content: static function (Gentor $model) use ($urlGenerator): A {
            return

            Html::a(
                'index',
                $urlGenerator->generate('generator/_index', ['id' => $model->getGentorId()]),
                ['class' => 'btn btn-secondary btn-sm ms-2'],
            );
        },
        encodeContent: false,
    ),
    new DataColumn(
        'id',
        header: '_view',
        content: static function (Gentor $model) use ($urlGenerator): A {
            return
            Html::a(
                '_view',
                $urlGenerator->generate('generator/_view', ['id' => $model->getGentorId()]),
                ['class' => 'btn btn-secondary btn-sm ms-2'],
            );
        },
        encodeContent: false,
    ),
    new DataColumn(
        'id',
        header: '_form',
        content: static function (Gentor $model) use ($urlGenerator): A {
            return

            Html::a(
                '_form',
                $urlGenerator->generate('generator/_form', ['id' => $model->getGentorId()]),
                ['class' => 'btn btn-secondary btn-sm ms-2'],
            );
        },
        encodeContent: false,
    ),
    new DataColumn(
        'id',
        header: '_route',
        content: static function (Gentor $model) use ($urlGenerator): A {
            return

            Html::a(
                '_route',
                $urlGenerator->generate('generator/_route', ['id' => $model->getGentorId()]),
                ['class' => 'btn btn-secondary btn-sm ms-2'],
            );
        },
        encodeContent: false,
    ),
];

$toolbarString
    =  new Form()->post($urlGenerator->generate('generator/index'))->csrf($csrf)->open()
    .  new A()
    ->href($urlGenerator->generate('generator/add'))
    ->addClass('btn btn-info')
    ->content('➕')
    ->render()
    .  new Div()->addClass('float-end m-3')->content($toolbarReset)->encode(false)->render()
    .  new Form()->close();

$gridSummary = $s->gridSummary(
    $paginator,
    $translator,
    (int) $s->getSetting('default_list_limit'),
    $translator->translate('generators'),
    '',
);

echo GridView::widget()
->bodyRowAttributes(['class' => 'align-middle'])
->tableAttributes(['class' => 'table table-striped text-center h-75','id' => 'table-generator'])
->columns(...$columns)
->dataReader($paginator)
->headerRowAttributes(['class' => 'card-header bg-info text-black'])
->header($translator->translate('generator'))
->id('w21-grid')
->paginationWidget($gridComponents->offsetPaginationWidget($paginator))
->summaryAttributes(['class' => 'mt-3 me-3 summary text-end'])
->summaryTemplate($gridSummary)
->noResultsCellAttributes(['class' => 'card-header bg-warning text-black'])
->noResultsText($translator->translate('no.records'))
->toolbar($toolbarString);
