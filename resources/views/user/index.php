<?php

declare(strict_types=1);

use App\User\User;
use Yiisoft\Data\Paginator\OffsetPaginator;
use Yiisoft\Data\Paginator\PageToken;
use Yiisoft\Data\Reader\Sort;
use Yiisoft\Data\Reader\OrderHelper;
use Yiisoft\Html\Html;
use Yiisoft\Html\Tag\A;
use Yiisoft\Html\Tag\Button;
use Yiisoft\Html\Tag\Div;
use Yiisoft\Html\Tag\Form;
use Yiisoft\Html\Tag\I;
use Yiisoft\Yii\DataView\GridView\Column\DataColumn;
use Yiisoft\Yii\DataView\GridView\GridView;
use Yiisoft\Yii\DataView\YiiRouter\UrlCreator;

/** 
 * @var App\Invoice\Setting\SettingRepository $s
 * @var App\Widget\GridComponents $gridComponents
 * @var App\Widget\PageSizeLimiter $pageSizeLimiter  
 * @var Yiisoft\Data\Cycle\Reader\EntityReader $users 
 * @var Yiisoft\Data\Paginator\OffsetPaginator $sortedAndPagedPaginator
 * @var Yiisoft\Router\CurrentRoute $currentRoute
 * @var Yiisoft\Router\FastRoute\UrlGenerator $urlGenerator
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var Yiisoft\Yii\DataView\YiiRouter\UrlCreator $urlCreator 
 * @var Yiisoft\View\WebView $this 
 * @var int $defaultPageSizeOffsetPaginator
 * @var string $csrf 
 * @var string $sortString 
 * @psalm-var positive-int $page  
 */

$this->setTitle($translator->translate('menu.users'));

$sort = Sort::only([
    'id',
    'login',
    'created_at',
    'updated_at',
])
// (Related logic: see vendor\yiisoft\data\src\Reader\Sort
// - => 'desc'  so -id => default descending on id
->withOrderString($sortString);

$sortedAndPagedPaginator = (new OffsetPaginator($users))
    ->withPageSize(
        $defaultPageSizeOffsetPaginator > 0 ?
            $defaultPageSizeOffsetPaginator : 1
    )
    ->withCurrentPage($page)
    ->withSort($sort)
    ->withToken(PageToken::next((string) $page));

$urlCreator = new UrlCreator($urlGenerator);
$urlCreator->__invoke([], OrderHelper::stringToArray($sortString));

$gridSummary = $s->gridSummary(
    $sortedAndPagedPaginator,
    $translator,
    $defaultPageSizeOffsetPaginator,
    $translator->translate('users'),
    '',
);

$toolbarApplyChange =  new Button()
    ->addClass('btn btn-success me-1')
    ->content( new I()->addClass('bi bi-check-all'))
    ->id('btn-apply-changes')
    ->type('submit')
    ->render();

$toolbarReset =  new A()
    ->addAttributes(['type' => 'reset'])
    ->addClass('btn btn-danger me-1 ajax-loader')
    ->content( new I()->addClass('bi bi-bootstrap-reboot'))
    ->href($urlGenerator->generate($currentRoute->getName() ?? 'user/index'))
    ->id('btn-reset')
    ->render();

echo new Div();
?>

<div>
    <div class="text-end">
        <?= Html::a('API v1 Info', $urlGenerator->generate('api/info/v1'), ['class' => 'btn btn-link']) ?>
        <?= Html::a('API v2 Info', $urlGenerator->generate('api/info/v2'), ['class' => 'btn btn-link']) ?>
        <?= Html::a('API Users List Data', $urlGenerator->generate('api/user/index'), ['class' => 'btn btn-link'])?>
    </div>
</div>
<?php
    $columns = [
        new DataColumn(
            'id',
            content: static function (User $data): string {
                return (string) $data->getId();
            },
        ),
        new DataColumn(
            'login',
            content: static fn (User $data) => $data->getLogin(),
            header: $translator->translate('gridview.login'),
        ),
        new DataColumn(
            'create_at',
            content: static fn (User $data) => $data->getCreatedAt()->format('r'),
            header: $translator->translate('gridview.create.at'),
        ),
        new DataColumn(
            'api',
            content: static function (User $data) use ($urlGenerator): A {
                return Html::a(
                    'API User Data',
                    $urlGenerator->generate(
                        'api/user/profile',
                        ['login' => $data->getLogin()],
                    ),
                    ['target' => '_blank'],
                );
            },
            header: $translator->translate('gridview.api'),
        ),
        new DataColumn(
            'profile',
            content: static function (User $data) use ($urlGenerator): A {
                return Html::a(
                    Html::tag('i', '', [
                        'class' => 'bi bi-person-fill ms-1',
                        'style' => 'font-size: 1.5em;',
                    ]),
                    $urlGenerator->generate('user/profile', ['login' => $data->getLogin()]),
                    ['class' => 'btn btn-link'],
                );
            },
            header: $translator->translate('gridview.profile'),
        ),
    ];
?>
<?php
$toolbarString
    =  new Form()->post($urlGenerator->generate('user/index'))->csrf($csrf)->open()
    .  new Div()->addClass('float-end m-3')->content($toolbarApplyChange
            . $toolbarReset)->encode(false)->render()
    .  new Form()->close();
echo GridView::widget()
->dataReader($sortedAndPagedPaginator)
->urlCreator($urlCreator)
->sortableHeaderPrepend('<div class="float-end text-secondary text-opacity-50">⭥</div>')
->sortableHeaderAscPrepend('<div class="float-end fw-bold">⭡</div>')
->sortableHeaderDescPrepend('<div class="float-end fw-bold">⭣</div>')        
->bodyRowAttributes(['class' => 'align-middle'])
->tableAttributes(['class' => 'table table-hover'])
->columns(...$columns)
->header($translator->translate('gridview.title'))
->id('w1-grid')
->summaryAttributes(['class' => 'summary text-end mb-5'])
->summaryTemplate('<div class="d-flex align-items-center">'
        . $pageSizeLimiter::buttons(
            $currentRoute, $s, $translator, $urlGenerator, 'user')
        . ' ' . $gridSummary . '</div>')  
->noResultsCellAttributes(['class' => 'card-header bg-warning text-black'])
->noResultsText($translator->translate('no.records'))        
->toolbar($toolbarString);
?>