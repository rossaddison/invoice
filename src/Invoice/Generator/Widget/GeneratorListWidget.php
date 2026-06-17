<?php

declare(strict_types=1);

namespace App\Invoice\Generator\Widget;

use App\Infrastructure\Persistence\Gentor\Gentor;
use App\Invoice\GeneratorRelation\GeneratorRelationRepository;
use Yiisoft\Data\Paginator\OffsetPaginator;
use Yiisoft\Data\Reader\OrderHelper;
use Yiisoft\Html\Html;
use Yiisoft\Html\Tag\A;
use Yiisoft\Html\Tag\Div;
use Yiisoft\Html\Tag\Form;
use Yiisoft\Html\Tag\I;
use Yiisoft\Router\CurrentRoute;
use Yiisoft\Router\UrlGeneratorInterface;
use Yiisoft\Translator\TranslatorInterface;
use Yiisoft\Widget\Widget;
use Yiisoft\Yii\DataView\GridView\Column\ActionButton;
use Yiisoft\Yii\DataView\GridView\Column\ActionColumn;
use Yiisoft\Yii\DataView\GridView\Column\DataColumn;
use Yiisoft\Yii\DataView\GridView\GridView;
use Yiisoft\Yii\DataView\Pagination\OffsetPagination;
use Yiisoft\Yii\DataView\Pagination\PaginationWidgetInterface;
use Yiisoft\Yii\DataView\YiiRouter\UrlCreator;

final class GeneratorListWidget extends Widget
{
    private const string DOM_ID = 'GeneratorGridView';
    private const string ROUTE_INDEX = 'generator/index';
    private const string BTN_CODE = 'btn btn-secondary btn-sm ms-2';

    private ?OffsetPaginator $paginator = null;
    private ?GeneratorRelationRepository $grR = null;
    private string|\Stringable $csrf = '';
    private string $gridSummary = '';
    private string $sortString = '-id';

    public function __construct(
        private readonly CurrentRoute $currentRoute,
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly TranslatorInterface $translator,
    ) {
    }

    public function withPaginator(OffsetPaginator $paginator): static
    {
        $new = clone $this;
        $new->paginator = $paginator;
        return $new;
    }

    public function withGrR(GeneratorRelationRepository $grR): static
    {
        $new = clone $this;
        $new->grR = $grR;
        return $new;
    }

    public function withCsrf(string|\Stringable $csrf): static
    {
        $new = clone $this;
        $new->csrf = $csrf;
        return $new;
    }

    public function withGridSummary(string $gridSummary): static
    {
        $new = clone $this;
        $new->gridSummary = $gridSummary;
        return $new;
    }

    public function withSortString(string $sortString): static
    {
        $new = clone $this;
        $new->sortString = $sortString;
        return $new;
    }

    #[\Override]
    public function render(): string
    {
        if ($this->paginator === null || $this->grR === null) {
            return '';
        }

        $htmxAttrs = [
            'hx-indicator'   => '#' . self::DOM_ID,
            'hx-target'      => '#' . self::DOM_ID,
            'hx-select'      => '#' . self::DOM_ID,
            'hx-replace-url' => 'true',
            'hx-swap'        => 'outerHTML',
        ];

        /** @var PaginationWidgetInterface<\Yiisoft\Data\Paginator\PaginatorInterface> */
        $pagination = OffsetPagination::widget()->addLinkAttributes([
            'hx-boost' => 'true',
            ...$htmxAttrs,
        ]);

        $urlCreator = new UrlCreator($this->urlGenerator);
        $urlCreator->__invoke([], OrderHelper::stringToArray($this->sortString));

        return GridView::widget()
            ->containerAttributes(['id' => self::DOM_ID, 'class' => 'position-relative'])
            ->bodyRowAttributes(['class' => 'align-middle'])
            ->tableAttributes(['class' => 'table table-striped text-center h-75', 'id' => 'table-generator'])
            ->columns(...$this->buildColumns())
            ->dataReader($this->paginator)
            ->urlCreator($urlCreator)
            ->paginationWidget($pagination)
            ->sortableLinkAttributes(['hx-boost' => 'true', ...$htmxAttrs])
            ->headerRowAttributes(['class' => 'card-header bg-info text-black'])
            ->header($this->translator->translate('generator'))
            ->summaryAttributes(['class' => 'mt-3 me-3 summary text-end'])
            ->summaryTemplate($this->gridSummary)
            ->noResultsCellAttributes(['class' => 'card-header bg-warning text-black'])
            ->noResultsText($this->translator->translate('no.records'))
            ->toolbar($this->buildToolbarString())
            ->render();
    }

    private function buildToolbarString(): string
    {
        $ug = $this->urlGenerator;

        $toolbarReset = (new A())
            ->addAttributes(['type' => 'reset'])
            ->addClass('btn btn-danger me-1 ajax-loader')
            ->content(new I()->addClass('bi bi-bootstrap-reboot'))
            ->href($ug->generate($this->currentRoute->getName() ?? self::ROUTE_INDEX))
            ->id('btn-reset')
            ->render();

        $addGenerator = (new A())
            ->href($ug->generate('generator/add'))
            ->addClass('btn btn-info')
            ->addAttributes(['hx-boost' => 'false'])
            ->content('➕')
            ->render();

        return (new Form())
                ->post($ug->generate(self::ROUTE_INDEX))
                ->csrf($this->csrf)
                ->open()
            . $addGenerator
            . (new Div())->addClass('float-end m-3')->content($toolbarReset)->encode(false)->render()
            . (new Form())->close();
    }

    /** @return list<DataColumn|ActionColumn> */
    private function buildColumns(): array
    {
        \assert($this->grR !== null);

        $grR     = $this->grR;
        $ug      = $this->urlGenerator;
        $t       = $this->translator;
        $btnCode = self::BTN_CODE;

        return [
            new DataColumn(
                'id',
                header: $t->translate('id'),
                content: static fn (Gentor $m): string => Html::encode(
                    $m->reqGentorId() . '➡️' . $m->getCamelcaseCapitalName()
                ),
                encodeContent: false,
            ),
            new DataColumn(
                'id',
                header: $t->translate('generator.relations'),
                content: static function (Gentor $m) use ($ug, $grR): string {
                    $entityLink = Html::a(
                        Html::encode($m->getCamelcaseCapitalName()),
                        $ug->generate('generator/view', ['id' => $m->reqGentorId()]),
                        ['class' => 'btn btn-primary btn-sm active', 'aria-current' => 'page'],
                    )->render();

                    $relationsHtml = '';
                    /** @var \App\Infrastructure\Persistence\GentorRelation\GentorRelation $relation */
                    foreach ($grR->repoGeneratorquery($m->reqGentorId()) as $relation) {
                        $relationsHtml .= Html::a(
                            $relation->getLowercaseName() ?? '#',
                            $ug->generate('generatorrelation/edit', ['id' => $relation->reqRelationId()]),
                            ['class' => 'btn btn-primary btn-sm'],
                        )->render();
                    }

                    return Html::openTag('div', ['class' => 'btn-group'])
                        . $entityLink
                        . $relationsHtml
                        . Html::closeTag('div');
                },
                encodeContent: false,
            ),
            $this->buildActionColumn($ug, $t),
            new DataColumn(
                'id',
                header: 'Entity',
                content: static fn (Gentor $m): A => Html::a(
                    'Entity' . DIRECTORY_SEPARATOR . $m->getCamelcaseCapitalName(),
                    $ug->generate('generator/entity', ['id' => $m->reqGentorId()]),
                    ['class' => $btnCode, 'hx-boost' => 'false'],
                ),
                encodeContent: false,
            ),
            new DataColumn(
                'id',
                header: 'Controller',
                content: static fn (Gentor $m): A => Html::a(
                    $m->getCamelcaseCapitalName() . 'Controller',
                    $ug->generate('generator/controller', ['id' => $m->reqGentorId()]),
                    ['class' => $btnCode, 'hx-boost' => 'false'],
                ),
                encodeContent: false,
            ),
            new DataColumn(
                'id',
                header: 'Form',
                content: static fn (Gentor $m): A => Html::a(
                    $m->getCamelcaseCapitalName() . 'Form',
                    $ug->generate('generator/form', ['id' => $m->reqGentorId()]),
                    ['class' => $btnCode, 'hx-boost' => 'false'],
                ),
                encodeContent: false,
            ),
            new DataColumn(
                'id',
                header: 'Repository',
                content: static fn (Gentor $m): A => Html::a(
                    $m->getCamelcaseCapitalName() . 'Repository',
                    $ug->generate('generator/repo', ['id' => $m->reqGentorId()]),
                    ['class' => $btnCode, 'hx-boost' => 'false'],
                ),
                encodeContent: false,
            ),
            new DataColumn(
                'id',
                header: 'Service',
                content: static fn (Gentor $m): A => Html::a(
                    $m->getCamelcaseCapitalName() . 'Service',
                    $ug->generate('generator/service', ['id' => $m->reqGentorId()]),
                    ['class' => $btnCode, 'hx-boost' => 'false'],
                ),
                encodeContent: false,
            ),
            new DataColumn(
                'id',
                header: 'index',
                content: static fn (Gentor $m): A => Html::a(
                    'index',
                    $ug->generate('generator/_index', ['id' => $m->reqGentorId()]),
                    ['class' => $btnCode, 'hx-boost' => 'false'],
                ),
                encodeContent: false,
            ),
            new DataColumn(
                'id',
                header: '_view',
                content: static fn (Gentor $m): A => Html::a(
                    '_view',
                    $ug->generate('generator/_view', ['id' => $m->reqGentorId()]),
                    ['class' => $btnCode, 'hx-boost' => 'false'],
                ),
                encodeContent: false,
            ),
            new DataColumn(
                'id',
                header: '_form',
                content: static fn (Gentor $m): A => Html::a(
                    '_form',
                    $ug->generate('generator/_form', ['id' => $m->reqGentorId()]),
                    ['class' => $btnCode, 'hx-boost' => 'false'],
                ),
                encodeContent: false,
            ),
            new DataColumn(
                'id',
                header: '_route',
                content: static fn (Gentor $m): A => Html::a(
                    '_route',
                    $ug->generate('generator/_route', ['id' => $m->reqGentorId()]),
                    ['class' => $btnCode, 'hx-boost' => 'false'],
                ),
                encodeContent: false,
            ),
        ];
    }

    private function buildActionColumn(UrlGeneratorInterface $ug, TranslatorInterface $t): ActionColumn
    {
        return new ActionColumn(
            before: Html::openTag('div', ['class' => 'btn-group', 'role' => 'group']),
            after: Html::closeTag('div'),
            buttons: [
                new ActionButton(
                    content: '🔎',
                    url: static fn (Gentor $m): string =>
                        $ug->generate('generator/view', ['id' => $m->reqGentorId()]),
                    attributes: [
                        'class'          => 'btn btn-outline-secondary btn-sm',
                        'data-bs-toggle' => 'tooltip',
                        'title'          => $t->translate('view'),
                        'hx-boost'       => 'false',
                    ],
                ),
                new ActionButton(
                    content: '✎',
                    url: static fn (Gentor $m): string =>
                        $ug->generate('generator/edit', ['id' => $m->reqGentorId()]),
                    attributes: [
                        'class'          => 'btn btn-outline-primary btn-sm',
                        'data-bs-toggle' => 'tooltip',
                        'title'          => $t->translate('edit'),
                        'hx-boost'       => 'false',
                    ],
                ),
                new ActionButton(
                    content: '❌',
                    url: static fn (Gentor $m): string =>
                        $ug->generate('generator/delete', ['id' => $m->reqGentorId()]),
                    attributes: [
                        'class'   => 'btn btn-outline-danger btn-sm',
                        'title'   => $t->translate('delete'),
                        'onclick' => "return confirm('" . $t->translate('delete.record.warning') . "');",
                        'hx-boost' => 'false',
                    ],
                ),
            ],
        );
    }
}
